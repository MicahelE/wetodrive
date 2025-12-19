<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
                            'filename' => $progress['filename'] ?? 'Unknown'
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

                            // Clean up cache
                            Cache::forget("transfer_progress_{$transferId}");
                            Cache::forget("transfer_result_{$transferId}");
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
    public static function updateProgress(string $transferId, int $bytesTransferred, int $totalBytes, string $filename = null, string $status = 'transferring')
    {
        $percentage = $totalBytes > 0 ? round(($bytesTransferred / $totalBytes) * 100, 2) : 0;

        $progressData = [
            'bytesTransferred' => $bytesTransferred,
            'totalBytes' => $totalBytes,
            'percentage' => $percentage,
            'status' => $status,
            'filename' => $filename,
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
     * Mark transfer as completed
     */
    public static function completeTransfer(string $transferId, bool $success = true)
    {
        $progress = Cache::get("transfer_progress_{$transferId}");

        if ($progress) {
            $progress['status'] = $success ? 'completed' : 'failed';
            $progress['percentage'] = $success ? 100 : $progress['percentage'];
            Cache::put("transfer_progress_{$transferId}", $progress, 60); // Keep for 1 minute to ensure client gets it
        }
    }
}