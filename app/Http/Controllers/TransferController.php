<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use App\Mail\OversizeTransferAlertMail;
use App\Mail\TransferCompleteMail;
use App\Mail\TransferFailedMail;
use App\Mail\UpgradeNudgeMail;
use App\Services\DriveFolderService;
use App\Services\StreamTransferService;
use App\Services\ResumableDownloader;
use App\Http\Controllers\StreamProgressController;
use App\Models\SubscriptionPlan;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TransferController extends Controller
{
    /**
     * The homepage. The previous design is preserved verbatim at
     * resources/views/home-legacy.blade.php: to roll back, return that view
     * instead and drop the $stats argument.
     */
    public function index()
    {
        // Live figures from this database. On production that is production data;
        // locally it is a near-empty dev db, which is why the view hides these
        // entirely when there is nothing real to show.
        //
        // Transfer count and byte total both come from the transfers table so they
        // stay consistent with each other. Note users.total_transfers sums higher
        // (144 vs 130 rows in prod as of 2026-08-13), so both figures here are a
        // conservative floor. The view says "over" for that reason.
        // The stats are decorative, but this page was a static view before the
        // redesign and now touches the database. A db hiccup must not take down
        // the page carrying most of the site's search traffic, so failure just
        // hides the section (the view already skips it when transfers is 0).
        try {
            $stats = Cache::remember('home-stats', now()->addMinutes(10), fn () => [
                'accounts' => User::count(),
                'transfers' => Transfer::count(),
                'bytes' => (int) Transfer::sum('file_size'),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Homepage stats unavailable, rendering without them', ['error' => $e->getMessage()]);
            $stats = ['accounts' => 0, 'transfers' => 0, 'bytes' => 0];
        }

        // A transfer keeps running after the tab closes (see transfer()), so a
        // returning user gets handed the id back and the page reattaches to the
        // live progress stream.
        $activeTransfer = Auth::check()
            ? StreamProgressController::activeTransferFor(Auth::id())
            : null;

        // Folders this app made for this user. Under the drive.file scope these
        // are the only ones it can see, so this list is the whole picker.
        $recentFolders = Auth::check()
            ? Auth::user()->driveFolders()
                ->orderByDesc('last_used_at')
                ->limit(5)
                ->pluck('path')
                ->all()
            : [];

        return view('home', compact('stats', 'activeTransfer', 'recentFolders'));
    }

    /**
     * A short-lived Drive access token for the Google Picker.
     *
     * The Picker runs in the browser and needs the user's own token to show
     * their Drive. Handed out from here rather than rendered into the page so it
     * is never sitting in cached HTML, and so it is refreshed on demand rather
     * than going stale on a page left open.
     */
    public function pickerToken(Request $request)
    {
        try {
            $token = (new StreamTransferService())->getGoogleClient($request->user())->getAccessToken();

            $payload = [
                'token' => $token['access_token'] ?? null,
                'app_id' => config('services.google.picker_app_id'),
                'developer_key' => config('services.google.picker_key'),
            ];
            $status = 200;
        } catch (\Throwable $e) {
            Log::warning('Could not mint a picker token', ['user_id' => $request->user()->id, 'error' => $e->getMessage()]);

            $payload = ['error' => 'Reconnect your Google Drive to browse folders.'];
            $status = 401;
        }

        // no-store on both paths: this carries an access token, and a cached copy
        // in a proxy or the browser would outlive the token's usefulness.
        return response()->json($payload, $status)->header('Cache-Control', 'no-store');
    }

    public function transfer(Request $request)
    {
        set_time_limit(0); // No time limit for streaming large files
        // PHP-FPM defaults to 128M, which the 10MB chunk buffer + Google SDK
        // upload layer can exceed on large files (see StreamTransferService).
        ini_set('memory_limit', '512M');

        $request->validate([
            'wetransfer_url' => 'required|url',
            'use_streaming' => 'boolean',
            'destination_folder' => 'nullable|string|max:255',
            // Set when the folder came from the Google Picker, in which case it
            // already exists and must not be created from its name.
            'destination_folder_id' => 'nullable|string|max:255',
        ]);

        if (!Auth::check()) {
            Log::warning('Transfer attempted without Google Drive authentication');

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Please connect to Google Drive first.'
                ], 401);
            }

            return redirect()->back()->with('error', 'Please connect to Google Drive first.');
        }

        $user = Auth::user();
        $useStreaming = $request->get('use_streaming', true); // Default to streaming

        // Check subscription limits
        if (!$this->checkTransferLimits($user)) {
            Log::warning('Transfer attempted but user exceeded limits', [
                'user_id' => $user->id,
                'subscription_tier' => $user->subscription_tier
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'You have reached your transfer limit for this month. Please upgrade your plan to continue.'
                ], 403);
            }

            return redirect()->back()->with('error',
                'You have reached your transfer limit for this month. ' .
                '<a href="' . route('subscription.pricing') . '" style="color: #4285f4; text-decoration: underline;">Upgrade your plan</a> to continue transferring files.'
            );
        }

        // Resolved here, while the request is still in hand. Every transfer path
        // below sends its response and detaches before doing any work, so a bad
        // folder resolved down there would fail invisibly in a background worker.
        try {
            $pickedId = $request->input('destination_folder_id');

            if ($pickedId) {
                // Chosen through the Picker, so it already exists in their Drive
                // and picking is what granted us access to it. Creating anything
                // here would make a duplicate beside the folder they chose.
                $folderId = $pickedId;
                $folderLabel = $request->input('destination_folder') ?: 'Selected folder';
                DriveFolderService::for($user)->remember($user, $folderLabel, $pickedId);
            } else {
                // Normalised first, on its own: it is pure string work, so a bad
                // path is rejected without ever touching Google.
                $folderLabel = DriveFolderService::normalizePath($request->input('destination_folder'));
                $folderId = DriveFolderService::for($user)->resolve($user, $folderLabel);
            }

            // Confirm we can actually write there before a single byte moves. The
            // Picker offers read-only shared folders too, and without this the
            // whole transfer downloads and then fails on every file.
            if ($folderId && !DriveFolderService::for($user)->canAddFilesTo($folderId)) {
                $message = 'You do not have permission to add files to that folder. '
                    . 'Pick a folder you own, or ask its owner for edit access.';

                Log::warning('Destination folder is not writable', [
                    'user_id' => $user->id,
                    'folder_id' => $folderId,
                    'folder' => $folderLabel,
                ]);

                if ($request->ajax()) {
                    return response()->json(['success' => false, 'error' => $message], 403);
                }

                return redirect()->back()->with('error', $message);
            }
        } catch (\InvalidArgumentException $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Could not resolve destination folder', [
                'user_id' => $user->id,
                'folder' => $request->input('destination_folder'),
                'error' => $e->getMessage(),
            ]);

            $message = 'Could not open that Google Drive folder. Please try again or reconnect your account.';

            if ($request->ajax()) {
                return response()->json(['success' => false, 'error' => $message], 500);
            }

            return redirect()->back()->with('error', $message);
        }

        try {
            $wetransferUrl = $request->wetransfer_url;
            Log::info('Starting WeTransfer process', [
                'url' => $wetransferUrl,
                'use_streaming' => $useStreaming,
                'folder_id' => $folderId,
                'is_ajax' => $request->ajax()
            ]);

            if ($useStreaming) {
                // Use new streaming approach
                return $this->transferWithStreaming($wetransferUrl, $user, $request, $folderId, $folderLabel);
            } else {
                // Use legacy disk-based approach
                return $this->transferWithDisk($wetransferUrl, $user, $folderId);
            }
        } catch (\Exception $e) {
            Log::error('Transfer failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->ajax()) {
                $errorMessage = $e->getMessage();
                $isWeTransferError = str_starts_with($errorMessage, 'WETRANSFER_EXPIRED:');

                if ($isWeTransferError) {
                    return response()->json([
                        'success' => false,
                        'error' => 'This WeTransfer link appears to be expired, password-protected, or no longer available.',
                        'is_wetransfer_error' => true,
                        'suggestions' => [
                            'Check if the link has expired (WeTransfer links expire after 7 days)',
                            'Ask the sender for a new link',
                            'Make sure the link isn\'t password-protected'
                        ]
                    ], 410);
                }

                return response()->json([
                    'success' => false,
                    'error' => 'Transfer failed: ' . $errorMessage
                ], 500);
            }

            return redirect()->back()->with('error', 'Transfer failed: ' . $e->getMessage());
        }
    }

    /**
     * Transfer using direct streaming (no temporary files)
     */
    private function transferWithStreaming(string $wetransferUrl, $user, Request $request, ?string $folderId = null, string $folderLabel = "")
    {
        $streamService = new StreamTransferService();
        $transferId = uniqid('transfer_', true);

        try {
            // Ask what is inside the transfer before fetching anything. When the
            // files can be taken individually we never touch the archive at all,
            // which is both the point of the feature and one fewer large download.
            $pageUrl = $streamService->resolvePageUrl($wetransferUrl);
            $listing = $streamService->listItems($pageUrl);
            $items = $listing['items'];
            $downloadUrl = null;

            if ($items) {
                // Sizes come from the manifest, so no stream is opened to weigh it.
                $fileInfo = [
                    'filename' => $listing['title'],
                    'size' => $listing['size'] ?: array_sum(array_column($items, 'size')),
                    'mimeType' => 'application/octet-stream',
                ];

                Log::info('Transfer can be imported file by file', [
                    'file_count' => count($items),
                    'total_size' => $fileInfo['size'],
                ]);
            } else {
                // Falls back to the whole archive: a folder upload, or anything
                // whose shape we do not recognise. Delivering the zip is never wrong.
                $downloadUrl = $streamService->parseWeTransferUrl($wetransferUrl);
                Log::info('Parsed download URL for streaming', ['download_url' => $downloadUrl]);

                $fileInfo = [];
                $streamService->getWeTransferStream($downloadUrl, $fileInfo);

                Log::info('Got WeTransfer stream', [
                    'filename' => $fileInfo['filename'],
                    'size' => $fileInfo['size'],
                    'mimeType' => $fileInfo['mimeType']
                ]);
            }

            // Validate file size against subscription limits, claiming the
            // one-time trial allowance atomically if this transfer needs it.
            [$maxSize, $claimedTrial] = $this->resolveFileSizeLimit($user, $fileInfo['size']);

            if ($fileInfo['size'] > $maxSize) {
                if ($claimedTrial) {
                    $user->releaseTrialTransfer(); // file still too big — don't burn the trial
                }

                Log::warning('File size exceeds user plan limit', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'subscription_tier' => $user->subscription_tier ?? 'free',
                    'filename' => $fileInfo['filename'],
                    'file_size' => $fileInfo['size'],
                    'file_size_formatted' => $this->formatFileSize($fileInfo['size']),
                    'max_allowed' => $maxSize,
                    'max_allowed_formatted' => $this->formatFileSize($maxSize),
                    'exceeded_by' => $fileInfo['size'] - $maxSize,
                    'exceeded_by_formatted' => $this->formatFileSize($fileInfo['size'] - $maxSize)
                ]);

                $this->alertAdminsIfUnservable($user, $fileInfo);
                $this->nudgeUserToUpgrade($user, $fileInfo, $maxSize);

                $payload = $this->limitErrorPayload($user, $fileInfo, $maxSize);

                // For Ajax request, return JSON error with the recommended plan.
                if ($request->ajax()) {
                    return response()->json(array_merge(
                        ['success' => false, 'error' => $payload['message']],
                        $payload,
                    ), 400);
                }

                return redirect()->back()->with('error',
                    e($payload['message']) . ' ' .
                    '<a href="' . $payload['upgrade_url'] . '" style="color: #4285f4; text-decoration: underline;">See plans</a>.'
                );
            }

            // The homepage reads this back to reattach its progress bar after a
            // reload, so a user who closes the tab mid-transfer is not stranded.
            // Cached rather than kept in the session so it survives on another
            // device, and expires on its own.
            // ponytail: one live transfer per user, which is all the UI supports.
            Cache::put("active_transfer_{$user->id}", $transferId, 900);

            // Import the files individually whenever we can. A single-file
            // transfer is just a batch of one, so this is the ordinary path
            // rather than a special case, and gets exercised constantly.
            if ($items && $request->ajax()) {
                return $this->importBatch(
                    $pageUrl, $items, $user, $transferId, $folderId, $claimedTrial,
                    $fileInfo['size'], $listing['title'], $streamService, $folderLabel
                );
            }

            // Everything below works on the whole archive. Reached either by the
            // fallback above (where it is already resolved) or by a non-Ajax post
            // of a listable transfer, which still needs the link.
            $downloadUrl ??= $streamService->parseWeTransferUrl($wetransferUrl);

            // For files < 1GB, use disk-based approach (more reliable)
            if ($fileInfo['size'] < 1024 * 1024 * 1024) {
                Log::info('Using disk-based transfer for file < 1GB', [
                    'size' => $fileInfo['size'],
                    'size_mb' => round($fileInfo['size'] / 1048576, 2)
                ]);
                return $this->transferWithDiskAsync(
                    $downloadUrl, $user, $request, $fileInfo, $transferId, $claimedTrial,
                    // Re-mint a fresh direct link (new 10-min token) on resume.
                    fn () => $streamService->parseWeTransferUrl($wetransferUrl),
                    $folderId
                );
            }

            // For Ajax requests with files >= 1GB, use streaming approach
            if ($request->ajax()) {
                Log::info('[AJAX] Processing Ajax transfer request (async)', [
                    'transfer_id' => $transferId,
                    'filename' => $fileInfo['filename'],
                    'size' => $fileInfo['size'],
                    'size_mb' => round($fileInfo['size'] / 1048576, 2),
                    'mime_type' => $fileInfo['mimeType'] ?? 'unknown',
                    'user_id' => $user->id,
                    'timestamp' => now()->toIso8601String()
                ]);

                // Initialize progress in cache
                StreamProgressController::updateProgress($transferId, 0, $fileInfo['size'], $fileInfo['filename'], 'starting');

                // Prepare and send response immediately
                $response = response()->json([
                    'success' => true,
                    'transfer_id' => $transferId,
                    'filename' => $fileInfo['filename'],
                    'size' => $fileInfo['size'],
                    'status' => 'processing'
                ]);

                Log::info('[AJAX] Sending immediate response, will continue in background', [
                    'transfer_id' => $transferId
                ]);

                // Send response to client immediately
                $response->send();

                // Flush output and close connection to client
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                } else {
                    // Fallback for non-FPM environments
                    if (ob_get_level() > 0) {
                        ob_end_flush();
                    }
                    flush();
                }

                // Continue processing in background
                ignore_user_abort(true);
                set_time_limit(0);

                try {
                    $transferStartTime = microtime(true);

                    // Set progress callback for real-time updates
                    $streamService->setProgressCallback(function($uploaded, $total) use ($transferId, $fileInfo) {
                        StreamProgressController::updateProgress(
                            $transferId,
                            $uploaded,
                            $total,
                            $fileInfo['filename'],
                            'transferring'
                        );
                    });

                    // Perform the actual transfer
                    Log::info('[AJAX] Starting background stream transfer', [
                        'transfer_id' => $transferId,
                        'download_url_length' => strlen($downloadUrl)
                    ]);

                    $googleDriveFileId = $streamService->streamTransfer($downloadUrl, $fileInfo, $user, $transferId, $folderId);

                    $transferEndTime = microtime(true);
                    $transferDuration = $transferEndTime - $transferStartTime;
                    $transferSpeed = ($fileInfo['size'] / $transferDuration) / 1048576; // MB/s

                    Log::info('[AJAX] Background transfer completed successfully', [
                        'filename' => $fileInfo['filename'],
                        'file_id' => $googleDriveFileId,
                        'transfer_id' => $transferId,
                        'duration_seconds' => round($transferDuration, 2),
                        'speed_mbps' => round($transferSpeed, 2),
                        'timestamp' => now()->toIso8601String()
                    ]);

                    // Increment transfer count after successful upload
                    $user->incrementTransferCount();

                    // Log the transfer with file size
                    Transfer::create([
                        'user_id' => $user->id,
                        'filename' => $fileInfo['filename'],
                        'file_size' => $fileInfo['size'],
                        'google_drive_id' => $googleDriveFileId,
                        'transferred_at' => now(),
                    ]);

                    // Mark transfer as complete and store result
                    StreamProgressController::completeTransfer($transferId, true, [
                        'success' => true,
                        'google_drive_id' => $googleDriveFileId,
                        'filename' => $fileInfo['filename'],
                        // Nudge non-paid users to upgrade at the moment of value.
                        'show_upgrade_prompt' => !$user->hasActiveSubscription(),
                    ]);

                    try {
                        $driveUrl = "https://drive.google.com/file/d/{$googleDriveFileId}/view";
                        Log::info('Sending transfer complete email', ['user_email' => $user->email, 'filename' => $fileInfo['filename']]);
                        Mail::to($user)->send(new TransferCompleteMail(
                            $user,
                            $fileInfo['filename'],
                            $this->formatFileSize($fileInfo['size']),
                            $driveUrl,
                        ));
                        Log::info('Transfer complete email sent', ['user_email' => $user->email, 'filename' => $fileInfo['filename']]);
                    } catch (\Exception $mailEx) {
                        Log::warning('Failed to send transfer complete email', [
                            'error' => $mailEx->getMessage(),
                            'exception' => get_class($mailEx),
                            'trace' => $mailEx->getTraceAsString(),
                        ]);
                    }

                } catch (\Exception $e) {
                    $errorTime = isset($transferStartTime) ? microtime(true) - $transferStartTime : 0;
                    $errorMessage = $e->getMessage();
                    $needsReconnect = false;

                    // Check for insufficient scopes error
                    if (str_contains($errorMessage, 'insufficient authentication scopes') ||
                        str_contains($errorMessage, 'ACCESS_TOKEN_SCOPE_INSUFFICIENT') ||
                        str_contains($errorMessage, 'Insufficient Permission')) {
                        $errorMessage = 'Your Google Drive connection needs to be refreshed with updated permissions.';
                        $needsReconnect = true;
                    }

                    Log::error('[AJAX] Background transfer failed', [
                        'transfer_id' => $transferId,
                        'error' => $e->getMessage(),
                        'needs_reconnect' => $needsReconnect,
                        'error_class' => get_class($e),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine(),
                        'time_until_error' => round($errorTime, 2),
                        'trace' => $e->getTraceAsString()
                    ]);

                    // Failed before success — return the trial if this transfer claimed it.
                    if ($claimedTrial) {
                        $user->releaseTrialTransfer();
                    }

                    // Mark transfer as failed
                    StreamProgressController::completeTransfer($transferId, false, [
                        'success' => false,
                        'error' => $errorMessage,
                        'needs_reconnect' => $needsReconnect
                    ]);

                    try {
                        Log::info('Sending transfer failed email', ['user_email' => $user->email, 'filename' => $fileInfo['filename']]);
                        Mail::to($user)->send(new TransferFailedMail($user, $fileInfo['filename'], $errorMessage));
                        Log::info('Transfer failed email sent', ['user_email' => $user->email, 'filename' => $fileInfo['filename']]);
                    } catch (\Exception $mailEx) {
                        Log::warning('Failed to send transfer failed email', [
                            'error' => $mailEx->getMessage(),
                            'exception' => get_class($mailEx),
                            'trace' => $mailEx->getTraceAsString(),
                        ]);
                    }
                }

                // Response already sent, just return
                return;
            }

            // For non-Ajax requests, process synchronously
            $googleDriveFileId = $streamService->streamTransfer($downloadUrl, $fileInfo, $user, $transferId, $folderId);

            Log::info('File streamed to Google Drive successfully', [
                'filename' => $fileInfo['filename'],
                'file_id' => $googleDriveFileId
            ]);

            // Increment transfer count after successful upload
            $user->incrementTransferCount();

            // Log the transfer with file size
            Transfer::create([
                'user_id' => $user->id,
                'filename' => $fileInfo['filename'],
                'file_size' => $fileInfo['size'],
                'google_drive_id' => $googleDriveFileId,
                'transferred_at' => now(),
            ]);

            $googleDriveUrl = "https://drive.google.com/file/d/{$googleDriveFileId}/view";

            try {
                Log::info('Sending transfer complete email', ['user_email' => $user->email, 'filename' => $fileInfo['filename']]);
                Mail::to($user)->send(new TransferCompleteMail(
                    $user,
                    $fileInfo['filename'],
                    $this->formatFileSize($fileInfo['size']),
                    $googleDriveUrl,
                ));
                Log::info('Transfer complete email sent', ['user_email' => $user->email, 'filename' => $fileInfo['filename']]);
            } catch (\Exception $mailEx) {
                Log::warning('Failed to send transfer complete email', [
                    'error' => $mailEx->getMessage(),
                    'exception' => get_class($mailEx),
                    'trace' => $mailEx->getTraceAsString(),
                ]);
            }

            $successMessage = 'File transferred to Google Drive successfully! ' .
                '<a href="' . $googleDriveUrl . '" target="_blank" style="color: #4285f4; text-decoration: underline; font-weight: 600;">📁 View in Google Drive</a>';

            return redirect()->back()->with('success', $successMessage);

        } catch (\Exception $e) {
            Log::error('Streaming transfer failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->ajax()) {
                $errorMessage = $e->getMessage();
                $isWeTransferError = str_starts_with($errorMessage, 'WETRANSFER_EXPIRED:');

                if ($isWeTransferError) {
                    return response()->json([
                        'success' => false,
                        'error' => 'This WeTransfer link appears to be expired, password-protected, or no longer available.',
                        'is_wetransfer_error' => true,
                        'suggestions' => [
                            'Check if the link has expired (WeTransfer links expire after 7 days)',
                            'Ask the sender for a new link',
                            'Make sure the link isn\'t password-protected'
                        ]
                    ], 410); // 410 Gone for expired content
                }

                return response()->json([
                    'success' => false,
                    'error' => $errorMessage
                ], 500);
            }

            throw $e;
        }
    }

    /**
     * Import each file in a transfer separately, rather than delivering the zip.
     *
     * A zip in Drive cannot be previewed, opened or searched inside, so for the
     * people this product is for it is delivered but not usable. This walks the
     * transfer's manifest and puts each file in Drive on its own.
     *
     * Responds and detaches once, up front, then does the whole batch in the
     * background: the transfer outlives the browser exactly as a single-file one
     * does. Quota is charged once for the link no matter how many files are in
     * it, which is how people think about a WeTransfer link.
     */
    private function importBatch(
        string $pageUrl,
        array $items,
        $user,
        string $transferId,
        ?string $folderId,
        bool $claimedTrial,
        int $totalSize,
        string $title,
        StreamTransferService $streamService,
        string $folderLabel = ''
    ) {
        $fileCount = count($items);

        StreamProgressController::updateProgress(
            $transferId, 0, $totalSize, $items[0]['name'], 'starting', 1, $fileCount
        );

        $response = response()->json([
            'success' => true,
            'transfer_id' => $transferId,
            'filename' => $title,
            'size' => $totalSize,
            'file_count' => $fileCount,
            'status' => 'processing',
        ]);

        $response->send();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            if (ob_get_level() > 0) {
                ob_end_flush();
            }
            flush();
        }

        ignore_user_abort(true);
        set_time_limit(0);

        $batchId = (string) Str::uuid();
        $delivered = [];
        $failed = [];
        $bytesDone = 0;
        $folders = DriveFolderService::for($user);

        foreach ($items as $index => $item) {
            $position = $index + 1;

            try {
                Log::info('[BATCH] Importing file', [
                    'transfer_id' => $transferId,
                    'batch_id' => $batchId,
                    'file' => $item['name'],
                    'position' => "{$position}/{$fileCount}",
                ]);

                // A folder upload arrives as files named "Dir/Sub/file.mov", so
                // the structure the sender had is rebuilt under the destination
                // rather than becoming a slash in the filename.
                $relativeDir = trim(str_replace('\\', '/', dirname($item['name'])), '.');
                $itemFolderId = $relativeDir === ''
                    ? $folderId
                    : $folders->resolveWithin($user, $folderId, $folderLabel, $relativeDir);

                $driveId = $this->importOneFile(
                    $streamService, $pageUrl, $item, $user, $transferId, $itemFolderId,
                    $bytesDone, $totalSize, $position, $fileCount
                );

                Transfer::create([
                    'user_id' => $user->id,
                    'batch_id' => $batchId,
                    'filename' => $item['name'],
                    'file_size' => $item['size'],
                    'google_drive_id' => $driveId,
                    'transferred_at' => now(),
                ]);

                $delivered[] = [
                    'filename' => $item['name'],
                    'google_drive_id' => $driveId,
                    'size' => $item['size'],
                ];
            } catch (\Throwable $e) {
                // One bad file does not throw away the ones already in Drive.
                Log::error('[BATCH] File failed, continuing with the rest', [
                    'transfer_id' => $transferId,
                    'file' => $item['name'],
                    'error' => $e->getMessage(),
                ]);

                $failed[] = ['filename' => $item['name'], 'error' => $e->getMessage()];
            }

            $bytesDone += $item['size'];
        }

        $this->finishBatch($user, $transferId, $folderId, $claimedTrial, $delivered, $failed, $title, $totalSize);

        return;
    }

    /**
     * Download one file out of a transfer and put it in Drive.
     *
     * Keeps the size rule the single-file path already used: stream the big ones
     * so they never touch the disk, buffer the small ones because it is more
     * reliable. $bytesBefore is what the batch had already moved, so the bar
     * tracks the whole transfer rather than restarting on every file.
     */
    private function importOneFile(
        StreamTransferService $streamService,
        string $pageUrl,
        array $item,
        $user,
        string $transferId,
        ?string $folderId,
        int $bytesBefore,
        int $totalSize,
        int $position,
        int $fileCount
    ): string {
        $link = $streamService->directLinkForItem($pageUrl, $item['id']);

        // Drive does not treat "/" as a path separator, so uploading the raw name
        // would make one flat file literally called "Shoot Day 1/Selects/x.mov".
        // The directories are folders in Drive; only the basename is the file.
        $item['name'] = basename(str_replace('\\', '/', $item['name']));

        $report = function (float $fraction, string $status) use ($transferId, $item, $bytesBefore, $totalSize, $position, $fileCount) {
            StreamProgressController::updateProgress(
                $transferId,
                (int) ($bytesBefore + $item['size'] * min(max($fraction, 0), 1)),
                $totalSize,
                $item['name'],
                $status,
                $position,
                $fileCount,
            );
        };

        // Large files stream straight through, no temp file involved.
        if ($item['size'] >= 1024 * 1024 * 1024) {
            $fileInfo = ['filename' => $item['name'], 'size' => $item['size'], 'mimeType' => 'application/octet-stream'];

            $streamService->setProgressCallback(function ($uploaded, $total) use ($report) {
                $report($total > 0 ? $uploaded / $total : 0, 'uploading');
            });

            return $streamService->streamTransfer($link, $fileInfo, $user, null, $folderId);
        }

        // Download counts as the first half of this file, upload as the second.
        $downloaded = $this->downloadFile(
            $link,
            fn ($done, $total) => $report($total > 0 ? ($done / $total) * 0.5 : 0, 'downloading'),
            fn () => $streamService->directLinkForItem($pageUrl, $item['id']),
        );

        // downloadFile takes the name from WeTransfer's content-disposition, and
        // for a folder upload that header carries the full path. Left alone, Drive
        // would hold a file literally named "Shoot Day 1/Selects/hero-take.mov"
        // sitting inside the Selects folder we just built for it.
        $downloaded['filename'] = $item['name'];

        try {
            return $this->uploadToGoogleDrive(
                $downloaded,
                $user,
                fn ($done, $total) => $report(0.5 + ($total > 0 ? ($done / $total) * 0.5 : 0), 'uploading'),
                $folderId,
            );
        } finally {
            // downloadFile writes to storage/temp; the disk fills up otherwise.
            if (!empty($downloaded['temp_file']) && file_exists($downloaded['temp_file'])) {
                @unlink($downloaded['temp_file']);
            }
        }
    }

    /**
     * Close out a batch: charge it once, tell the page, and email once.
     *
     * Quota is only consumed when something actually landed. A transfer where
     * every file failed has cost the user nothing, so it should not cost them a
     * transfer either.
     */
    private function finishBatch($user, string $transferId, ?string $folderId, bool $claimedTrial, array $delivered, array $failed, string $title, int $totalSize): void
    {
        $anyDelivered = count($delivered) > 0;

        if ($anyDelivered) {
            $user->incrementTransferCount();
        } elseif ($claimedTrial) {
            $user->releaseTrialTransfer();
        }

        $folderUrl = $folderId ? "https://drive.google.com/drive/folders/{$folderId}" : null;

        StreamProgressController::completeTransfer($transferId, $anyDelivered, [
            'success' => $anyDelivered,
            'files' => $delivered,
            'failed' => $failed,
            'file_count' => count($delivered),
            'folder_url' => $folderUrl,
            'filename' => $title,
            // A single-file batch keeps the old shape so the existing UI still works.
            'google_drive_id' => $anyDelivered && count($delivered) === 1 ? $delivered[0]['google_drive_id'] : null,
            'error' => $anyDelivered ? null : ($failed[0]['error'] ?? 'Transfer failed'),
            'show_upgrade_prompt' => $anyDelivered && !$user->hasActiveSubscription(),
        ]);

        try {
            $summary = count($delivered) === 1
                ? $delivered[0]['filename']
                : count($delivered) . ' files from ' . $title;

            if ($anyDelivered) {
                Mail::to($user)->send(new TransferCompleteMail(
                    $user,
                    $summary,
                    $this->formatFileSize($totalSize),
                    $folderUrl ?? 'https://drive.google.com/drive/my-drive',
                ));
            } else {
                Mail::to($user)->send(new TransferFailedMail($user, $title, $failed[0]['error'] ?? 'Transfer failed'));
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send batch transfer email', ['error' => $e->getMessage()]);
        }

        Log::info('[BATCH] Finished', [
            'transfer_id' => $transferId,
            'delivered' => count($delivered),
            'failed' => count($failed),
        ]);
    }

    /**
     * Transfer using disk-based approach with async AJAX pattern
     * Downloads to temp file, then uploads to Google Drive
     * Used for files < 1GB for better reliability
     */
    private function transferWithDiskAsync(string $downloadUrl, $user, Request $request, array $fileInfo, string $transferId, bool $claimedTrial = false, ?callable $refreshUrl = null, ?string $folderId = null)
    {
        if ($request->ajax()) {
            Log::info('[AJAX] Processing disk-based transfer (async)', [
                'transfer_id' => $transferId,
                'filename' => $fileInfo['filename'],
                'size' => $fileInfo['size'],
                'size_mb' => round($fileInfo['size'] / 1048576, 2),
                'user_id' => $user->id
            ]);

            // Initialize progress in cache
            StreamProgressController::updateProgress($transferId, 0, $fileInfo['size'], $fileInfo['filename'], 'starting');

            // Prepare and send response immediately
            $response = response()->json([
                'success' => true,
                'transfer_id' => $transferId,
                'filename' => $fileInfo['filename'],
                'size' => $fileInfo['size'],
                'status' => 'processing'
            ]);

            $response->send();

            // Flush output and close connection to client
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            } else {
                if (ob_get_level() > 0) {
                    ob_end_flush();
                }
                flush();
            }

            // Continue processing in background
            ignore_user_abort(true);
            set_time_limit(0);

            try {
                $totalSize = $fileInfo['size'];

                // Phase 1: Download from WeTransfer (0-50% progress)
                Log::info('[AJAX] Starting download phase', ['transfer_id' => $transferId]);

                $downloadedFileInfo = $this->downloadFile($downloadUrl, function($downloaded, $total) use ($transferId, $fileInfo, $totalSize) {
                    $percentage = $total > 0 ? ($downloaded / $total) * 50 : 0;
                    $bytesProgress = (int)($totalSize * $percentage / 100);
                    StreamProgressController::updateProgress($transferId, $bytesProgress, $totalSize, $fileInfo['filename'], 'downloading');
                }, $refreshUrl);

                Log::info('[AJAX] Download complete, starting upload phase', [
                    'transfer_id' => $transferId,
                    'temp_file' => $downloadedFileInfo['temp_file']
                ]);

                // Phase 2: Upload to Google Drive (50-100% progress)
                $googleDriveFileId = $this->uploadToGoogleDrive($downloadedFileInfo, $user, function($uploaded, $total) use ($transferId, $fileInfo, $totalSize) {
                    $percentage = 50 + ($total > 0 ? ($uploaded / $total) * 50 : 0);
                    $bytesProgress = (int)($totalSize * $percentage / 100);
                    StreamProgressController::updateProgress($transferId, $bytesProgress, $totalSize, $fileInfo['filename'], 'uploading');
                }, $folderId);

                Log::info('[AJAX] Disk-based transfer completed successfully', [
                    'transfer_id' => $transferId,
                    'google_drive_id' => $googleDriveFileId,
                    'filename' => $fileInfo['filename']
                ]);

                // Increment transfer count after successful upload
                $user->incrementTransferCount();

                // Log the transfer with file size
                Transfer::create([
                    'user_id' => $user->id,
                    'filename' => $fileInfo['filename'],
                    'file_size' => $fileInfo['size'],
                    'google_drive_id' => $googleDriveFileId,
                    'transferred_at' => now(),
                ]);

                // Mark transfer as complete and store result
                StreamProgressController::completeTransfer($transferId, true, [
                    'success' => true,
                    'google_drive_id' => $googleDriveFileId,
                    'filename' => $fileInfo['filename'],
                    // Nudge non-paid users to upgrade at the moment of value.
                    'show_upgrade_prompt' => !$user->hasActiveSubscription(),
                ]);

                try {
                    $driveUrl = "https://drive.google.com/file/d/{$googleDriveFileId}/view";
                    Log::info('Sending transfer complete email', ['user_email' => $user->email, 'filename' => $fileInfo['filename']]);
                    Mail::to($user)->send(new TransferCompleteMail(
                        $user,
                        $fileInfo['filename'],
                        $this->formatFileSize($fileInfo['size']),
                        $driveUrl,
                    ));
                    Log::info('Transfer complete email sent', ['user_email' => $user->email, 'filename' => $fileInfo['filename']]);
                } catch (\Exception $mailEx) {
                    Log::warning('Failed to send transfer complete email', [
                        'error' => $mailEx->getMessage(),
                        'exception' => get_class($mailEx),
                        'trace' => $mailEx->getTraceAsString(),
                    ]);
                }

            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                $needsReconnect = false;

                // Check for insufficient scopes error
                if (str_contains($errorMessage, 'insufficient authentication scopes') ||
                    str_contains($errorMessage, 'ACCESS_TOKEN_SCOPE_INSUFFICIENT') ||
                    str_contains($errorMessage, 'Insufficient Permission')) {
                    $errorMessage = 'Your Google Drive connection needs to be refreshed with updated permissions.';
                    $needsReconnect = true;
                }

                Log::error('[AJAX] Disk-based transfer failed', [
                    'transfer_id' => $transferId,
                    'error' => $e->getMessage(),
                    'needs_reconnect' => $needsReconnect,
                    'trace' => $e->getTraceAsString()
                ]);

                // Failed before success — return the trial if this transfer claimed it.
                if ($claimedTrial) {
                    $user->releaseTrialTransfer();
                }

                // Mark transfer as failed
                StreamProgressController::completeTransfer($transferId, false, [
                    'success' => false,
                    'error' => $errorMessage,
                    'needs_reconnect' => $needsReconnect
                ]);

                try {
                    Log::info('Sending transfer failed email', ['user_email' => $user->email, 'filename' => $fileInfo['filename']]);
                    Mail::to($user)->send(new TransferFailedMail($user, $fileInfo['filename'], $errorMessage));
                    Log::info('Transfer failed email sent', ['user_email' => $user->email, 'filename' => $fileInfo['filename']]);
                } catch (\Exception $mailEx) {
                    Log::warning('Failed to send transfer failed email', [
                        'error' => $mailEx->getMessage(),
                        'exception' => get_class($mailEx),
                        'trace' => $mailEx->getTraceAsString(),
                    ]);
                }
            }

            return;
        }

        // Non-AJAX: use legacy disk-based approach with redirects
        return $this->transferWithDisk($downloadUrl, $user, $folderId);
    }

/**
     * Transfer using legacy disk-based approach
     */
    private function transferWithDisk(string $wetransferUrl, $user, ?string $folderId = null)
    {
        $claimedTrial = false;

        try {
            $downloadUrl = $this->parseWeTransferUrl($wetransferUrl);
            Log::info('Parsed download URL', ['download_url' => $downloadUrl]);

            $fileInfo = $this->downloadFile(
                $downloadUrl,
                null,
                // Re-mint a fresh direct link (new 10-min token) on resume.
                fn () => $this->parseWeTransferUrl($wetransferUrl)
            );
            Log::info('File downloaded to disk', [
                'filename' => $fileInfo['filename'],
                'size' => $fileInfo['size'],
                'mimeType' => $fileInfo['mimeType'],
                'temp_file' => $fileInfo['temp_file']
            ]);

            // Validate file size against subscription limits, claiming the
            // one-time trial allowance atomically if this transfer needs it.
            [$maxSize, $claimedTrial] = $this->resolveFileSizeLimit($user, $fileInfo['size']);

            if ($fileInfo['size'] > $maxSize) {
                if ($claimedTrial) {
                    $user->releaseTrialTransfer(); // file still too big — don't burn the trial
                }

                // Cleanup temp file
                if (file_exists($fileInfo['temp_file'])) {
                    unlink($fileInfo['temp_file']);
                }

                Log::warning('File size exceeds user plan limit', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'subscription_tier' => $user->subscription_tier ?? 'free',
                    'filename' => $fileInfo['filename'],
                    'file_size' => $fileInfo['size'],
                    'file_size_formatted' => $this->formatFileSize($fileInfo['size']),
                    'max_allowed' => $maxSize,
                    'max_allowed_formatted' => $this->formatFileSize($maxSize),
                    'exceeded_by' => $fileInfo['size'] - $maxSize,
                    'exceeded_by_formatted' => $this->formatFileSize($fileInfo['size'] - $maxSize)
                ]);

                $this->alertAdminsIfUnservable($user, $fileInfo);
                $this->nudgeUserToUpgrade($user, $fileInfo, $maxSize);

                $payload = $this->limitErrorPayload($user, $fileInfo, $maxSize);

                return redirect()->back()->with('error',
                    e($payload['message']) . ' ' .
                    '<a href="' . $payload['upgrade_url'] . '" style="color: #4285f4; text-decoration: underline;">See plans</a>.'
                );
            }

            $googleDriveFileId = $this->uploadToGoogleDrive($fileInfo, $user, null, $folderId);
            Log::info('File uploaded to Google Drive successfully', [
                'filename' => $fileInfo['filename'],
                'file_id' => $googleDriveFileId
            ]);

            // Increment transfer count after successful upload
            $user->incrementTransferCount();

            // Log the transfer with file size
            Transfer::create([
                'user_id' => $user->id,
                'filename' => $fileInfo['filename'],
                'file_size' => $fileInfo['size'],
                'google_drive_id' => $googleDriveFileId,
                'transferred_at' => now(),
            ]);

            $googleDriveUrl = "https://drive.google.com/file/d/{$googleDriveFileId}/view";

            try {
                Log::info('Sending transfer complete email', ['user_email' => $user->email, 'filename' => $fileInfo['filename']]);
                Mail::to($user)->send(new TransferCompleteMail(
                    $user,
                    $fileInfo['filename'],
                    $this->formatFileSize($fileInfo['size']),
                    $googleDriveUrl,
                ));
                Log::info('Transfer complete email sent', ['user_email' => $user->email, 'filename' => $fileInfo['filename']]);
            } catch (\Exception $mailEx) {
                Log::warning('Failed to send transfer complete email', [
                    'error' => $mailEx->getMessage(),
                    'exception' => get_class($mailEx),
                    'trace' => $mailEx->getTraceAsString(),
                ]);
            }

            $successMessage = 'File transferred to Google Drive successfully! ' .
                '<a href="' . $googleDriveUrl . '" target="_blank" style="color: #4285f4; text-decoration: underline; font-weight: 600;">📁 View in Google Drive</a>';

            return redirect()->back()->with('success', $successMessage);
        } catch (\Exception $e) {
            // Failed before success — return the trial if this transfer claimed it.
            if ($claimedTrial) {
                $user->releaseTrialTransfer();
            }

            // Cleanup any temp files that might have been created
            if (isset($fileInfo['temp_file']) && file_exists($fileInfo['temp_file'])) {
                unlink($fileInfo['temp_file']);
                Log::info('Cleaned up temp file after error', ['temp_file' => $fileInfo['temp_file']]);
            }
            throw $e;
        }
    }

    private function parseWeTransferUrl($url)
    {
        Log::info('Parsing WeTransfer URL', ['original_url' => $url]);
        
        // Extract transfer ID from URL patterns like:
        // https://we.tl/t-XXXXXXXXXX
        // https://wetransfer.com/downloads/XXXXXXXXXX
        
        if (preg_match('/we\.tl\/t-([a-zA-Z0-9]+)/', $url, $matches)) {
            $transferId = $matches[1];
            Log::info('Found short URL transfer ID', ['transfer_id' => $transferId]);
            // First we need to resolve the short URL
            return $this->resolveShortUrl($url);
        }
        
        if (preg_match('/wetransfer\.com\/downloads\/([a-zA-Z0-9]+)/', $url, $matches)) {
            $transferId = $matches[1];
            Log::info('Found long URL transfer ID', ['transfer_id' => $transferId]);
            return $url;
        }
        
        Log::warning('Could not parse WeTransfer URL pattern', ['url' => $url]);
        throw new \Exception('Invalid WeTransfer URL format');
    }

    private function resolveShortUrl($shortUrl)
    {
        Log::info('Resolving WeTransfer short URL', ['short_url' => $shortUrl]);
        
        $client = new Client([
            'allow_redirects' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]
        ]);
        
        try {
            $response = $client->get($shortUrl);
            $locationHeader = $response->getHeader('Location')[0] ?? null;
            
            if ($locationHeader) {
                Log::info('Short URL resolved', ['resolved_url' => $locationHeader]);
                return $locationHeader;
            }
        } catch (\Exception $e) {
            Log::error('Failed to resolve short URL', ['error' => $e->getMessage()]);
        }
        
        return $shortUrl;
    }
    
    private function getDirectDownloadLink($pageUrl)
    {
        Log::info('Fetching direct download link from WeTransfer page', ['page_url' => $pageUrl]);
        
        // Shapes: /downloads/{transfer_id}/{security_hash} from the sender page,
        // /downloads/{transfer_id}/{recipient_id}/{security_hash} from the email.
        try {
            [$transferId, $securityHash, $recipientId] = StreamTransferService::parseDownloadUrl($pageUrl);
        } catch (\Exception $e) {
            Log::error('Could not extract transfer ID from URL', ['url' => $pageUrl]);
            throw $e;
        }
        
        Log::info('Extracted transfer details', [
            'transfer_id' => $transferId,
            'security_hash' => $securityHash
        ]);
        
        $cookieJar = new CookieJar();
        
        try {
            // First, fetch the page to get session and any necessary data
            $client = new Client([
                'cookies' => $cookieJar,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'DNT' => '1',
                    'Connection' => 'keep-alive',
                    'Upgrade-Insecure-Requests' => '1'
                ]
            ]);
            
            $pageResponse = $client->get($pageUrl);
            $html = $pageResponse->getBody()->getContents();
            
            Log::info('Fetched WeTransfer page', [
                'status' => $pageResponse->getStatusCode(),
                'html_length' => strlen($html),
                'html_preview' => substr($html, 0, 500)
            ]);
            
            // Look for state data in the page
            $stateData = null;
            
            // Try to extract __NEXT_DATA__ which contains the transfer info
            if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.+?)<\/script>/s', $html, $nextDataMatches)) {
                $nextData = json_decode($nextDataMatches[1], true);
                Log::info('Found __NEXT_DATA__', ['keys' => array_keys($nextData ?? [])]);
                
                if (isset($nextData['props']['pageProps'])) {
                    $stateData = $nextData['props']['pageProps'];
                    Log::info('Found pageProps', ['keys' => array_keys($stateData ?? [])]);
                }
            }
            
            // Extract CSRF token
            $csrfToken = null;
            if (preg_match('/name="csrf-token" content="([^"]+)"/', $html, $csrfMatches)) {
                $csrfToken = $csrfMatches[1];
                Log::info('Found CSRF token from meta tag');
            } elseif (preg_match('/"csrf_token":"([^"]+)"/', $html, $csrfMatches)) {
                $csrfToken = $csrfMatches[1];
                Log::info('Found CSRF token from JSON');
            }
            
            // Now make the API request to get the download link
            $apiUrl = "https://wetransfer.com/api/v4/transfers/{$transferId}/download";
            
            Log::info('Making API request', [
                'url' => $apiUrl,
                'has_csrf' => !empty($csrfToken)
            ]);
            
            $headers = [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'application/json, text/plain, */*',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Content-Type' => 'application/json',
                'Origin' => 'https://wetransfer.com',
                'Referer' => $pageUrl,
                'X-Requested-With' => 'XMLHttpRequest'
            ];
            
            if ($csrfToken) {
                $headers['X-CSRF-Token'] = $csrfToken;
            }
            
            $requestBody = [
                'security_hash' => $securityHash,
                'intent' => 'entire_transfer'
            ];

            // Recipient-scoped links 403 without this.
            if ($recipientId) {
                $requestBody['recipient_id'] = $recipientId;
            }

            if ($csrfToken) {
                $requestBody['csrf_token'] = $csrfToken;
            }
            
            $apiResponse = $client->post($apiUrl, [
                'json' => $requestBody,
                'headers' => $headers
            ]);
            
            $responseData = json_decode($apiResponse->getBody()->getContents(), true);
            
            Log::info('API response', [
                'status' => $apiResponse->getStatusCode(),
                'data' => $responseData
            ]);
            
            // Check various possible fields for the download URL
            if (isset($responseData['direct_link'])) {
                return $responseData['direct_link'];
            }
            
            if (isset($responseData['download_url'])) {
                return $responseData['download_url'];
            }
            
            if (isset($responseData['fields']['download_url'])) {
                return $responseData['fields']['download_url'];
            }
            
            // If we have a presigned URL structure
            if (isset($responseData['presigned_url'])) {
                return $responseData['presigned_url'];
            }
            
            Log::error('No download link found in API response', ['response' => $responseData]);
            
            // As a fallback, try to construct the direct download URL
            // WeTransfer sometimes uses a pattern like this
            $directUrl = "https://download.wetransfer.com/eugv/{$transferId}/{$securityHash}";
            Log::info('Trying fallback direct URL', ['url' => $directUrl]);
            
            return $directUrl;
            
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $body = $response ? $response->getBody()->getContents() : 'No response body';
            
            Log::error('API request failed with client error', [
                'status' => $response ? $response->getStatusCode() : 'unknown',
                'body' => $body,
                'error' => $e->getMessage()
            ]);
            
            throw new \Exception('WeTransfer API error: ' . $body);
            
        } catch (\Exception $e) {
            Log::error('Failed to get direct download link', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    private function downloadFile($url, $progressCallback = null, ?callable $refreshUrl = null)
    {
        Log::info('Starting file download', ['url' => $url]);

        $url = StreamTransferService::normalizeDownloadUrl($url);

        // If this is a WeTransfer page URL, get the direct download link first
        if (strpos($url, 'wetransfer.com/downloads') !== false) {
            $url = $this->getDirectDownloadLink($url);
            Log::info('Got direct download link', ['direct_url' => $url]);
        }

        // Create temp file path
        $tempDir = storage_path('temp');
        $tempFile = $tempDir . '/' . uniqid('wetransfer_', true) . '.tmp';

        // Ensure temp directory exists
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        Log::info('Downloading to temp file', ['temp_file' => $tempFile]);

        $clientOptions = [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ],
            'allow_redirects' => true,
            'timeout' => 0,
            'read_timeout' => 0,
            'connect_timeout' => 30
        ];

        // Add progress callback if provided
        if ($progressCallback) {
            $clientOptions['progress'] = function($downloadTotal, $downloadedBytes, $uploadTotal, $uploadedBytes) use ($progressCallback) {
                if ($downloadTotal > 0) {
                    $progressCallback($downloadedBytes, $downloadTotal);
                }
            };
        }

        $client = new Client($clientOptions);

        try {
            // First, get headers to extract filename and validate response
            $headResponse = $client->head($url);

            $contentType = $headResponse->getHeader('content-type')[0] ?? 'application/octet-stream';
            $contentLength = $headResponse->getHeader('content-length')[0] ?? 'unknown';

            Log::info('Response headers', [
                'content_type' => $contentType,
                'content_length' => $contentLength
            ]);

            // Check if we got HTML instead of a file
            if (strpos($contentType, 'text/html') !== false) {
                Log::error('Received HTML content type', ['content_type' => $contentType]);
                throw new \Exception('Download link appears to return a webpage instead of a file. The link may have expired or be invalid.');
            }

            $contentDisposition = $headResponse->getHeader('content-disposition')[0] ?? '';
            preg_match('/filename[^;=\n]*=(([\'"]).*?\2|[^;\n]*)/', $contentDisposition, $matches);
            $filename = isset($matches[1]) ? trim($matches[1], '"\'') : 'downloaded_file';

            Log::info('Extracted filename', ['filename' => $filename]);

            // Now stream the actual file download. WeTransfer's signed URLs
            // expire ~10 minutes after minting, so a slow/large download gets
            // its connection cut mid-stream (cURL error 18). Resume from the
            // last byte via Range requests, re-minting the URL between tries.
            $acceptRanges = stripos($headResponse->getHeaderLine('accept-ranges'), 'bytes') !== false;
            $expectedSize = ($contentLength !== 'unknown') ? (int) $contentLength : null;

            Log::info('Starting streaming download', [
                'expected_size' => $contentLength,
                'resumable' => $acceptRanges,
            ]);

            $downloader = new ResumableDownloader();
            $actualSize = $downloader->download(
                $tempFile,
                $url,
                $expectedSize,
                $refreshUrl,
                $progressCallback,
                $acceptRanges
            );

            Log::info('File streamed to disk', ['size_bytes' => $actualSize, 'temp_file' => $tempFile]);

            // Validate download completed successfully
            if ($contentLength !== 'unknown' && $actualSize != intval($contentLength)) {
                unlink($tempFile); // Cleanup incomplete file
                throw new \Exception("Download incomplete. Expected {$contentLength} bytes, got {$actualSize} bytes.");
            }

            return [
                'temp_file' => $tempFile,
                'filename' => $filename,
                'mimeType' => $contentType,
                'size' => $actualSize
            ];

        } catch (\Exception $e) {
            // Cleanup temp file on error
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }

            Log::error('File download failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function uploadToGoogleDrive($fileInfo, $user, $progressCallback = null, ?string $folderId = null)
    {
        Log::info('Starting Google Drive upload', [
            'filename' => $fileInfo['filename'],
            'size' => $fileInfo['size'],
            'temp_file' => $fileInfo['temp_file'],
            'folder_id' => $folderId
        ]);
        
        $tempFile = $fileInfo['temp_file'];
        
        try {
            if (!file_exists($tempFile)) {
                throw new \Exception('Temporary file not found: ' . $tempFile);
            }
            
            $client = new Google_Client();
            
            // Configure the client with OAuth credentials
            $clientId = config('services.google.client_id');
            $clientSecret = config('services.google.client_secret');
            
            if (!$clientId || !$clientSecret) {
                Log::error('Google OAuth credentials not configured', [
                    'has_client_id' => !empty($clientId),
                    'has_client_secret' => !empty($clientSecret)
                ]);
                throw new \Exception('Google OAuth credentials are not properly configured.');
            }
            
            $client->setClientId($clientId);
            $client->setClientSecret($clientSecret);
            $client->setRedirectUri(config('services.google.redirect'));
            
            // Parse the stored token
            $token = is_string($user->google_token) ? json_decode($user->google_token, true) : $user->google_token;
            
            // Set the access token with refresh token if available
            if (is_array($token)) {
                $client->setAccessToken($token);
            } else {
                // Fallback for old format
                $client->setAccessToken($user->google_token);
            }
            
            // Check if token needs refresh
            if ($client->isAccessTokenExpired()) {
                Log::info('Google token expired, attempting refresh');
                
                // Check if we have a refresh token
                $refreshToken = $user->google_refresh_token;
                
                if (!$refreshToken && is_array($token) && isset($token['refresh_token'])) {
                    $refreshToken = $token['refresh_token'];
                }
                
                if (!$refreshToken) {
                    Log::error('No refresh token available');
                    throw new \Exception('Your Google Drive session has expired. Please reconnect to Google Drive.');
                }
                
                $client->fetchAccessTokenWithRefreshToken($refreshToken);
                $newToken = $client->getAccessToken();
                
                // Store the new token
                $user->google_token = json_encode($newToken);
                $user->save();
                Log::info('Google token refreshed successfully');
            }

            $service = new Google_Service_Drive($client);

            $metadata = ['name' => $fileInfo['filename']];

            // Omitted rather than null: Drive reads a missing parents key as
            // "My Drive root", which is the pre-folder behaviour.
            if ($folderId) {
                $metadata['parents'] = [$folderId];
            }

            $fileMetadata = new Google_Service_Drive_DriveFile($metadata);

            Log::info('Uploading to Google Drive from disk', [
                'filename' => $fileInfo['filename'],
                'size' => $fileInfo['size'],
                'mimeType' => $fileInfo['mimeType']
            ]);
            
            // For large files, use resumable upload
            if ($fileInfo['size'] > 5 * 1024 * 1024) { // > 5MB
                Log::info('Using resumable upload for large file');
                
                // Enable resumable upload
                $client->setDefer(true);
                
                $request = $service->files->create($fileMetadata, [
                    'mimeType' => $fileInfo['mimeType'],
                    'uploadType' => 'resumable'
                ]);
                
                // Create media upload
                $media = new \Google_Http_MediaFileUpload(
                    $client,
                    $request,
                    $fileInfo['mimeType'],
                    null,
                    true,
                    1024 * 1024 // 1MB chunks
                );
                $media->setFileSize($fileInfo['size']);
                
                // Upload file in chunks
                $status = false;
                $handle = fopen($tempFile, "rb");
                $uploaded = 0;
                $totalSize = $fileInfo['size'];

                while (!$status && !feof($handle)) {
                    $chunk = fread($handle, 1024 * 1024); // 1MB chunks
                    $status = $media->nextChunk($chunk);
                    $uploaded += strlen($chunk);

                    // Call progress callback if provided
                    if ($progressCallback && $totalSize > 0) {
                        $progressCallback($uploaded, $totalSize);
                    }
                }

                fclose($handle);
                $client->setDefer(false);

                $result = $status;
                
            } else {
                // For smaller files, use simple upload
                Log::info('Using simple upload for small file');

                $result = $service->files->create($fileMetadata, [
                    'data' => file_get_contents($tempFile),
                    'mimeType' => $fileInfo['mimeType'],
                    'uploadType' => 'multipart'
                ]);

                // Call progress callback with 100% for simple upload
                if ($progressCallback) {
                    $progressCallback($fileInfo['size'], $fileInfo['size']);
                }
            }
            
            Log::info('Google Drive upload successful', [
                'file_id' => $result->id,
                'filename' => $result->name
            ]);
            
            return $result->id;
            
        } catch (\Exception $e) {
            Log::error('Google Drive upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        } finally {
            // Always cleanup the temp file
            if (file_exists($tempFile)) {
                unlink($tempFile);
                Log::info('Cleaned up temp file', ['temp_file' => $tempFile]);
            }
        }
    }

    private function checkTransferLimits($user): bool
    {
        // For paid subscriptions, check via subscription model
        if ($user->hasActiveSubscription()) {
            return $user->activeSubscription->canMakeTransfer();
        }

        // Free tier: simplified check - allow up to 5 total transfers
        // In a real app, you'd want to track monthly transfers properly
        return $user->total_transfers < 5;
    }

    /**
     * Decide the max file size for this transfer and, when the file actually
     * needs the one-time trial allowance, atomically claim it. Returns
     * [int $maxSize, bool $claimedTrial]. Claiming up-front (rather than at
     * completion) closes the race where several large transfers started before
     * the first finishes all pass the size check on the same unused trial.
     * If the caller then rejects the file, it must releaseTrialTransfer().
     */
    private function resolveFileSizeLimit($user, int $size): array
    {
        $freeLimit = 100 * 1024 * 1024; // 100MB

        if ($user->hasActiveSubscription()) {
            return [$user->activeSubscription->subscriptionPlan->max_file_size, false];
        }

        if ($size <= $freeLimit) {
            return [$freeLimit, false]; // fits free tier — trial untouched
        }

        if ($user->claimTrialTransfer()) {
            return [3 * 1024 * 1024 * 1024, true]; // trial claimed atomically (3GB)
        }

        return [$freeLimit, false]; // trial already used/claimed — capped at free tier
    }

    /**
     * Alert the admins when a file is too big for EVERY plan — there's no upgrade
     * to sell, so it's a demand signal rather than a conversion opportunity.
     * Throttled to one alert per user per day; a blocked user typically retries
     * the same file several times in a row.
     */
    private function alertAdminsIfUnservable($user, array $fileInfo): void
    {
        try {
            $topLimit = (int) SubscriptionPlan::max('max_file_size');

            if ($topLimit <= 0 || $fileInfo['size'] <= $topLimit) {
                return; // an upgrade would cover this — normal upsell path
            }

            if (! Cache::add("oversize-alert:{$user->id}", true, now()->addDay())) {
                return; // already alerted for this user today
            }

            $admins = User::where('role', 'admin')->get();
            if ($admins->isEmpty()) {
                return;
            }

            Mail::to($admins)->send(new OversizeTransferAlertMail(
                $user,
                $fileInfo['filename'],
                $this->formatFileSize($fileInfo['size']),
                $this->formatFileSize($topLimit),
                $this->formatFileSize($fileInfo['size'] - $topLimit),
            ));
        } catch (\Throwable $e) {
            // An alert must never break the user's request.
            Log::warning('Failed to send oversize transfer alert', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Email a free user who just bounced off the size limit, nudging them to the
     * plan that would carry the file. Only fires when an upgrade actually helps
     * (the file fits some plan), the user hasn't opted out of marketing, and we
     * haven't already nudged them recently. Mirrors alertAdminsIfUnservable: a
     * nudge must never break the user's request.
     */
    private function nudgeUserToUpgrade($user, array $fileInfo, int $maxSize): void
    {
        try {
            if (($user->email_opt_out ?? false) || ($user->role ?? null) === 'admin') {
                return;
            }

            $plan = $this->recommendPlanFor($fileInfo['size']);
            if (! $plan) {
                return; // bigger than every plan — nothing to upsell (admin alert covers this)
            }

            // ponytail: shared cooldown with the batch backlog send, so retries
            // and a re-run of emails:upgrade-nudge can't stack copies on someone.
            if (! Cache::add("upgrade-nudge:{$user->id}", true, now()->addDays(7))) {
                return;
            }

            Mail::to($user)->send(new UpgradeNudgeMail(
                $user,
                $this->formatFileSize($fileInfo['size']),
                $plan->name,
                $plan->getFormattedPriceForCountry($user->country_code ?? 'US'),
            ));
        } catch (\Throwable $e) {
            Log::warning('Failed to send upgrade nudge email', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * The cheapest active plan whose file-size cap covers $size, or null when the
     * file is bigger than every plan (the unservable case). Plans are ordered by
     * sort_order, which tracks the price ladder.
     */
    private function recommendPlanFor(int $size): ?SubscriptionPlan
    {
        return SubscriptionPlan::active()
            ->where('max_file_size', '>=', $size)
            ->orderBy('sort_order')
            ->first();
    }

    /**
     * Build the "file too large" payload, naming the exact plan the file needs so
     * the user doesn't buy the wrong tier (the mistake that cost us #382 and Alicia).
     * Shared by the ajax JSON and the redirect message so the two never diverge.
     *
     * @return array{message: string, is_limit_error: bool, upgrade_url: string, recommended_plan?: string, recommended_plan_name?: string, recommended_plan_price?: string, file_size?: string, current_limit?: string}
     */
    private function limitErrorPayload($user, array $fileInfo, int $maxSize): array
    {
        $fileSize = $this->formatFileSize($fileInfo['size']);
        $plan = $this->recommendPlanFor($fileInfo['size']);

        if (! $plan) {
            // Bigger than every plan — nothing to sell, so be honest.
            return [
                'message' => "This file ({$fileSize}) is larger than any of our plans can handle. Please get in touch and we'll help.",
                'is_limit_error' => true,
                'upgrade_url' => route('subscription.pricing'),
            ];
        }

        $price = $plan->getFormattedPriceForCountry($user->country_code ?? 'US');

        return [
            'message' => "Your {$fileSize} file needs the {$plan->name} plan ({$price}/mo). Your current limit is {$this->formatFileSize($maxSize)}.",
            'is_limit_error' => true,
            'recommended_plan' => $plan->slug,
            'recommended_plan_name' => $plan->name,
            'recommended_plan_price' => $price,
            'file_size' => $fileSize,
            'current_limit' => $this->formatFileSize($maxSize),
            'upgrade_url' => route('subscription.pricing', ['recommended' => $plan->slug]),
        ];
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 1) . 'GB';
        } elseif ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1) . 'MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 1) . 'KB';
        }
        return $bytes . ' bytes';
    }
}
