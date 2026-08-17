<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StreamProgressController extends Controller
{
    /**
     * Stream progress updates via Server-Sent Events
     */
    public function streamProgress(Request $request)
    {
        set_time_limit(0); // No time limit for SSE long-polling

        $transferId = $request->get('transfer_id');

        if (!$transferId) {
            return response()->json(['error' => 'Transfer ID required'], 400);
        }

        // Transfer ids are guessable enough that this stream would otherwise hand
        // out someone else's filename and byte counts. Checked once, here, rather
        // than inside the loop: the pointer lives as long as the progress key, so
        // a user returning to a just-finished transfer still passes.
        if (Cache::get('active_transfer_' . Auth::id()) !== $transferId) {
            return response()->json(['error' => 'Not your transfer'], 403);
        }

        $response = new StreamedResponse(function () use ($transferId) {
            // Disable output buffering for real-time streaming
            while (ob_get_level()) {
                ob_end_clean();
            }

            $lastProgress = null;
            $maxRetries = 600; // 10 minutes max (600 * 1 second)
            $retries = 0;

            while ($retries < $maxRetries) {
                // Get progress from cache
                $progress = Cache::get("transfer_progress_{$transferId}", null);

                if ($progress !== null) {
                    // Only send update if progress changed
                    if ($progress != $lastProgress) {
                        $data = [
                            'bytesTransferred' => $progress['bytesTransferred'] ?? 0,
                            'totalBytes' => $progress['totalBytes'] ?? 0,
                            'percentage' => $progress['percentage'] ?? 0,
                            'status' => $progress['status'] ?? 'transferring',
                            'filename' => $progress['filename'] ?? 'Unknown',
                            'fileIndex' => $progress['fileIndex'] ?? null,
                            'fileCount' => $progress['fileCount'] ?? null
                        ];

                        echo "data: " . json_encode($data) . "\n\n";
                        flush();

                        $lastProgress = $progress;

                        // Check if transfer is complete
                        if ($progress['status'] === 'completed' || $progress['status'] === 'failed') {
                            // Get transfer result from cache (contains google_drive_id, error, etc.)
                            $result = Cache::get("transfer_result_{$transferId}", []);

                            // Send final status with result data
                            $completeData = array_merge(
                                ['status' => $progress['status']],
                                $result
                            );

                            echo "event: complete\n";
                            echo "data: " . json_encode($completeData) . "\n\n";
                            flush();

                            // Deliberately not forgetting the keys here. Draining
                            // the stream used to destroy the result, so a user who
                            // closed the tab and came back saw nothing at all. The
                            // 15 minute TTL cleans up instead.
                            break;
                        }
                    }
                }

                // Send heartbeat to keep connection alive
                if ($retries % 30 == 0) {
                    echo "event: ping\n";
                    echo "data: {\"time\": " . time() . "}\n\n";
                    flush();
                }

                sleep(1);
                $retries++;
            }

            // Timeout - send timeout event
            if ($retries >= $maxRetries) {
                echo "event: timeout\n";
                echo "data: {\"message\": \"Transfer timed out\"}\n\n";
                flush();

                Cache::forget("transfer_progress_{$transferId}");
            }
        });

        // Set SSE headers on the response object
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Connection', 'keep-alive');

        return $response;
    }

    /**
     * Update transfer progress (called internally by transfer service)
     */
    public static function updateProgress(string $transferId, int $bytesTransferred, int $totalBytes, ?string $filename = null, string $status = 'transferring', ?int $fileIndex = null, ?int $fileCount = null)
    {
        $percentage = $totalBytes > 0 ? round(($bytesTransferred / $totalBytes) * 100, 2) : 0;

        $progressData = [
            'bytesTransferred' => $bytesTransferred,
            'totalBytes' => $totalBytes,
            'percentage' => $percentage,
            'status' => $status,
            'filename' => $filename,
            // Set only for multi-file transfers, so the UI can say "File 3 of 12"
            // while the bar tracks the batch rather than the current file.
            'fileIndex' => $fileIndex,
            'fileCount' => $fileCount,
            'timestamp' => time()
        ];

        // Store in cache for 15 minutes
        Cache::put("transfer_progress_{$transferId}", $progressData, 900);

        Log::debug('Progress updated', [
            'transfer_id' => $transferId,
            'percentage' => $percentage,
            'status' => $status
        ]);
    }

    /**
     * Mark transfer as completed, optionally with its result.
     *
     * ORDER MATTERS. streamProgress() watches the progress status and, the
     * instant it turns completed/failed, reads transfer_result_* and emits the
     * complete event. Writing the status first leaves a window where the result
     * is not there yet, and the page shows "Transfer Complete" with no files and
     * no link. Pass $result here so the two are always written the right way
     * round rather than relying on every caller to remember.
     */
    public static function completeTransfer(string $transferId, bool $success = true, ?array $result = null)
    {
        if ($result !== null) {
            Cache::put("transfer_result_{$transferId}", $result, 900);
        }

        $progress = Cache::get("transfer_progress_{$transferId}");

        if ($progress) {
            $progress['status'] = $success ? 'completed' : 'failed';
            $progress['percentage'] = $success ? 100 : $progress['percentage'];
            // 15 minutes, not 1: long enough that someone who closed the tab
            // mid-transfer still gets the result when they come back.
            Cache::put("transfer_progress_{$transferId}", $progress, 900);
        }
    }

    /**
     * The transfer this user currently has running, if any.
     *
     * The pointer alone is not enough — it can outlive the progress it points at
     * — so the progress key is what actually decides. Returns null once there is
     * nothing left to show, which is what keeps a stale id off the homepage.
     */
    public static function activeTransferFor(int $userId): ?string
    {
        $transferId = Cache::get("active_transfer_{$userId}");

        return $transferId && Cache::has("transfer_progress_{$transferId}")
            ? $transferId
            : null;
    }
}
