<?php

namespace App\Services;

use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\StreamWrapper;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\StreamInterface;
use App\Http\Controllers\StreamProgressController;

class StreamTransferService
{
    protected Client $httpClient;
    protected int $chunkSize;
    private $progressCallback = null;
    protected const GOOGLE_MIN_CHUNK_SIZE = 262144; // 256KB minimum for Google Drive

    public function __construct(?int $chunkSize = null)
    {
        // Get chunk size from config or use 10MB default (10 * 1024 * 1024)
        if ($chunkSize === null) {
            $chunkSize = config('services.google.chunk_size', 10485760); // 10MB default
        }

        // Ensure chunk size is at least the minimum required by Google Drive
        $this->chunkSize = max($chunkSize, self::GOOGLE_MIN_CHUNK_SIZE);

        Log::info('[STREAMING] StreamTransferService initialized', [
            'chunk_size' => $this->chunkSize,
            'chunk_size_mb' => round($this->chunkSize / 1048576, 2),
            'minimum_chunk_size' => self::GOOGLE_MIN_CHUNK_SIZE,
            'minimum_chunk_size_kb' => round(self::GOOGLE_MIN_CHUNK_SIZE / 1024, 2)
        ]);
        $this->httpClient = new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
            ],
            'allow_redirects' => true,
            'timeout' => 0,
            'read_timeout' => 0,
            'connect_timeout' => 30
        ]);
    }

    /**
     * Set a callback for progress updates
     */
    public function setProgressCallback(callable $callback): void
    {
        $this->progressCallback = $callback;
    }

    /**
     * Stream transfer directly from WeTransfer to Google Drive
     */
    public function streamTransfer(string $downloadUrl, array $fileInfo, $user, ?string $transferId = null, ?string $folderId = null): string
    {
        // Guarantee headroom for the chunk buffer + Google upload layer even
        // when invoked outside the controller entry point (FPM default is 128M,
        // which crashed large transfers at the buffer concat in streamToGoogleDrive).
        ini_set('memory_limit', '512M');

        Log::info('Starting streaming transfer', [
            'download_url' => $downloadUrl,
            'filename' => $fileInfo['filename'],
            'transfer_id' => $transferId
        ]);

        // Get WeTransfer download stream
        $downloadStream = $this->getWeTransferStream($downloadUrl, $fileInfo);

        // Upload stream to Google Drive with progress tracking
        $fileId = $this->streamToGoogleDrive($downloadStream, $fileInfo, $user, $transferId, $folderId);

        Log::info('Streaming transfer completed', [
            'file_id' => $fileId,
            'filename' => $fileInfo['filename']
        ]);

        return $fileId;
    }

    /**
     * Get a stream from WeTransfer URL
     */
    public function getWeTransferStream(string $url, array &$fileInfo): StreamInterface
    {
        Log::info('Opening WeTransfer stream', ['url' => $url]);

        // Get headers first to extract metadata
        $headResponse = $this->httpClient->head($url);

        $contentType = $headResponse->getHeader('content-type')[0] ?? 'application/octet-stream';
        $contentLength = $headResponse->getHeader('content-length')[0] ?? 0;
        $contentDisposition = $headResponse->getHeader('content-disposition')[0] ?? '';

        // Check if we got HTML instead of a file
        if (strpos($contentType, 'text/html') !== false) {
            throw new \Exception('Download link appears to return a webpage instead of a file. The link may have expired.');
        }

        // Extract filename if not already set
        if (!isset($fileInfo['filename']) || empty($fileInfo['filename'])) {
            preg_match('/filename[^;=\n]*=(([\'"]).*?\2|[^;\n]*)/', $contentDisposition, $matches);
            $fileInfo['filename'] = isset($matches[1]) ? trim($matches[1], '"\'') : 'downloaded_file';
        }

        // Update file info
        $fileInfo['size'] = intval($contentLength);
        $fileInfo['mimeType'] = $contentType;

        Log::info('Stream metadata', [
            'filename' => $fileInfo['filename'],
            'size' => $fileInfo['size'],
            'mimeType' => $fileInfo['mimeType']
        ]);

        // Open the actual download stream
        $response = $this->httpClient->get($url, ['stream' => true]);

        return $response->getBody();
    }

    /**
     * Parse WeTransfer URL and get direct download link
     */
    public function parseWeTransferUrl(string $url): string
    {
        Log::info('Parsing WeTransfer URL', ['url' => $url]);

        // Handle short URLs
        if (preg_match('/we\.tl\/t-([a-zA-Z0-9]+)/', $url, $matches)) {
            return $this->resolveShortUrl($url);
        }

        $url = self::normalizeDownloadUrl($url);

        // Handle full URLs - need to get direct download link
        if (strpos($url, 'wetransfer.com/downloads') !== false) {
            return $this->getDirectDownloadLink($url);
        }

        throw new \Exception('Invalid WeTransfer URL format');
    }

    /**
     * Resolve WeTransfer short URL to full URL
     */
    private function resolveShortUrl(string $shortUrl): string
    {
        Log::info('Resolving short URL', ['url' => $shortUrl]);

        $client = new Client([
            'allow_redirects' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
            ]
        ]);

        $response = $client->get($shortUrl);
        $locationHeader = $response->getHeader('Location')[0] ?? null;

        if (!$locationHeader) {
            throw new \Exception('Could not resolve short URL');
        }

        Log::info('Short URL resolved', ['resolved_url' => $locationHeader]);

        // Now get the direct download link from the resolved URL
        return $this->getDirectDownloadLink($locationHeader);
    }

    /**
     * Point a /previews/ URL at /downloads/ instead.
     *
     * WeTransfer serves the same transfer under both paths with identical ids,
     * and people paste whichever one their browser happens to be showing. We
     * only ever accepted /downloads/, so a preview link was rejected outright
     * as "Invalid WeTransfer URL format" — 93 times in the logs. Anything that
     * is not a preview URL comes back untouched.
     */
    public static function normalizeDownloadUrl(string $url): string
    {
        return preg_replace('#(wetransfer\.com)/previews/#', '$1/downloads/', $url, 1);
    }

    /**
     * Split a WeTransfer download URL into [transfer_id, security_hash, recipient_id].
     *
     * There are two link shapes and only one was handled. The sender's own page
     * gives /downloads/{id}/{hash}. The "you received files" email, which is how
     * most people arrive, gives /downloads/{id}/{recipient_id}/{hash} — and that
     * download is scoped to the recipient, so the API needs BOTH the hash (last
     * segment) and the recipient id (middle segment). We were sending the
     * recipient id as the hash and nothing else, so WeTransfer answered 403 on
     * every email link. Sending both returns 200 on the very same URLs.
     *
     * recipient_id is null for sender-page links, which have no recipient.
     */
    public static function parseDownloadUrl(string $pageUrl): array
    {
        // Three segments: the middle one is the recipient id.
        if (preg_match('#wetransfer\.com/downloads/([a-f0-9]+)/([a-f0-9]+)/([a-f0-9]+)#', $pageUrl, $m)) {
            return [$m[1], $m[3], $m[2]];
        }

        // Two segments: hash only.
        if (preg_match('#wetransfer\.com/downloads/([a-f0-9]+)/([a-f0-9]+)#', $pageUrl, $m)) {
            return [$m[1], $m[2], null];
        }

        throw new \Exception('Invalid WeTransfer URL format');
    }

    /**
     * Open a session against a transfer's download page.
     *
     * WeTransfer's API wants the cookies and CSRF token the page hands out, so
     * every API call starts here. Returns [client, headers, body, transferId,
     * securityHash] where body already carries the security hash and, for
     * recipient-scoped links, the recipient id that they 403 without.
     */
    private function apiSession(string $pageUrl): array
    {
        [$transferId, $securityHash, $recipientId] = self::parseDownloadUrl($pageUrl);

        $client = new Client([
            'cookies' => new CookieJar(),
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
            ]
        ]);

        $html = $client->get($pageUrl)->getBody()->getContents();

        $csrfToken = null;
        if (preg_match('/name="csrf-token" content="([^"]+)"/', $html, $m)) {
            $csrfToken = $m[1];
        } elseif (preg_match('/"csrf_token":"([^"]+)"/', $html, $m)) {
            $csrfToken = $m[1];
        }

        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Origin' => 'https://wetransfer.com',
            'Referer' => $pageUrl
        ];

        $body = ['security_hash' => $securityHash];

        if ($recipientId) {
            $body['recipient_id'] = $recipientId;
        }

        if ($csrfToken) {
            $headers['X-CSRF-Token'] = $csrfToken;
            $body['csrf_token'] = $csrfToken;
        }

        return [$client, $headers, $body, $transferId, $securityHash];
    }

    /**
     * POST to the WeTransfer API, retrying only what is worth retrying.
     *
     * WeTransfer returns transient 5xx and connection errors, so those get a
     * short backoff. A 4xx means expired, password-protected or invalid, where
     * retrying never helps — that fails fast with the WETRANSFER_EXPIRED: signal
     * the controller turns into the "ask the sender for a new link" message.
     */
    private function postApi(Client $client, string $url, array $headers, array $body, string $what): array
    {
        $maxAttempts = 3;
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = $client->post($url, ['json' => $body, 'headers' => $headers]);

                return json_decode($response->getBody()->getContents(), true) ?? [];
            } catch (\Throwable $e) {
                $lastException = $e;
                $statusCode = ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse())
                    ? $e->getResponse()->getStatusCode()
                    : 0;

                if ($statusCode >= 400 && $statusCode < 500) {
                    Log::warning('WeTransfer request unavailable', ['what' => $what, 'status' => $statusCode]);
                    throw new \Exception("WETRANSFER_EXPIRED:HTTP {$statusCode} from WeTransfer");
                }

                Log::warning('WeTransfer request failed, retrying', [
                    'what' => $what,
                    'attempt' => $attempt,
                    'status' => $statusCode,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $maxAttempts) {
                    sleep($attempt); // 1s, then 2s
                }
            }
        }

        $errorMessage = $lastException?->getMessage() ?? 'unknown error';
        Log::error('WeTransfer request exhausted retries', ['what' => $what, 'error' => $errorMessage]);

        if (str_contains($errorMessage, 'No download access') ||
            str_contains($errorMessage, '404') ||
            str_contains($errorMessage, 'expired') ||
            str_contains($errorMessage, 'not found') ||
            str_contains($errorMessage, 'Forbidden')) {
            throw new \Exception('WETRANSFER_EXPIRED:' . $errorMessage);
        }

        throw new \Exception('Failed to reach WeTransfer');
    }

    /**
     * The individual files inside a transfer.
     *
     * Returns ['title' => string, 'size' => int, 'items' => [['id','name','size'], ...]]
     * with items empty when the transfer cannot safely be taken apart, which
     * tells the caller to fall back to downloading the whole archive.
     *
     * Anything that is not a plain file is that case: WeTransfer lets people
     * upload whole folders, and those items do not have a single file behind
     * them. Delivering the zip is always correct, so an unfamiliar shape
     * degrades rather than fails.
     */
    public function listItems(string $pageUrl): array
    {
        [$client, $headers, $body, $transferId] = $this->apiSession($pageUrl);

        $data = $this->postApi(
            $client,
            "https://wetransfer.com/api/v4/transfers/{$transferId}/prepare-download",
            $headers,
            $body,
            'prepare-download',
        );

        return [
            'title' => $data['display_name'] ?? $data['recommended_filename'] ?? 'WeTransfer files',
            'size' => (int) ($data['size'] ?? 0),
            'items' => self::filterItems($data['items'] ?? []),
        ];
    }

    /**
     * Keep the manifest only when every entry is a plain file we can fetch.
     *
     * Returning [] means "deliver the archive instead". All-or-nothing on
     * purpose: importing half a transfer individually and silently dropping the
     * rest would lose the user's files, so an unfamiliar shape falls back whole.
     *
     * Static and pure so the rule can be tested without going near the network.
     */
    public static function filterItems(array $rawItems): array
    {
        $items = [];

        foreach ($rawItems as $item) {
            if (($item['item_type'] ?? null) !== 'file' || empty($item['id']) || !isset($item['name'])) {
                Log::info('Transfer contains a non-file item, falling back to the archive', [
                    'item_type' => $item['item_type'] ?? null,
                ]);

                return [];
            }

            $items[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'size' => (int) ($item['size'] ?? 0),
            ];
        }

        return $items;
    }

    /**
     * A download link for one file inside a transfer, rather than the whole zip.
     *
     * This is what makes the delivered files usable: a zip in Drive cannot be
     * previewed, opened or searched inside.
     */
    public function directLinkForItem(string $pageUrl, string $itemId): string
    {
        [$client, $headers, $body, $transferId] = $this->apiSession($pageUrl);

        $data = $this->postApi(
            $client,
            "https://wetransfer.com/api/v4/transfers/{$transferId}/download",
            $headers,
            $body + ['intent' => 'single_file', 'file_ids' => [$itemId]],
            'single-file download',
        );

        $link = $data['direct_link'] ?? $data['download_url'] ?? null;

        if (!$link) {
            throw new \Exception('WeTransfer returned no link for this file');
        }

        return $link;
    }

    /**
     * Get direct download link from WeTransfer page
     */
    private function getDirectDownloadLink(string $pageUrl): string
    {
        Log::info('Getting direct download link', ['page_url' => $pageUrl]);

        [$client, $headers, $body, $transferId, $securityHash] = $this->apiSession($pageUrl);

        $responseData = $this->postApi(
            $client,
            "https://wetransfer.com/api/v4/transfers/{$transferId}/download",
            $headers,
            $body + ['intent' => 'entire_transfer'],
            'entire-transfer download',
        );

        $downloadUrl = $responseData['direct_link']
            ?? $responseData['download_url']
            ?? $responseData['fields']['download_url']
            ?? $responseData['presigned_url']
            ?? null;

        if ($downloadUrl) {
            Log::info('Got direct download URL', ['url' => $downloadUrl]);
            return $downloadUrl;
        }

        $fallbackUrl = "https://download.wetransfer.com/eugv/{$transferId}/{$securityHash}";
        Log::info('Using fallback URL', ['url' => $fallbackUrl]);

        return $fallbackUrl;
    }

    /**
     * The /downloads/ page URL for a pasted link, short or long.
     *
     * listItems() and directLinkForItem() need the page URL rather than the
     * signed file URL that parseWeTransferUrl() returns.
     */
    public function resolvePageUrl(string $url): string
    {
        if (preg_match('/we\.tl\/t-([a-zA-Z0-9]+)/', $url, $m)) {
            $client = new Client([
                'allow_redirects' => false,
                'headers' => ['User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36']
            ]);

            $location = $client->get($url)->getHeader('Location')[0] ?? null;

            if (!$location) {
                throw new \Exception('Could not resolve short URL');
            }

            return self::assertDownloadable(self::normalizeDownloadUrl($location));
        }

        return self::normalizeDownloadUrl($url);
    }

    /**
     * A short link that no longer points at a transfer is expired, not malformed.
     *
     * WeTransfer answers a dead we.tl link with a redirect to
     * /redirect/error rather than an error status, and the caller then reported
     * "Invalid WeTransfer URL format" — which reads as though the user pasted
     * something wrong, when in fact the link is gone. This maps it onto the
     * expired signal, so they get told to ask the sender for a new link.
     */
    private static function assertDownloadable(string $resolved): string
    {
        if (!preg_match('#wetransfer\.com/downloads/#', $resolved)) {
            Log::warning('Short link no longer points at a transfer', ['resolved' => $resolved]);

            throw new \Exception('WETRANSFER_EXPIRED:short link resolved to ' . $resolved);
        }

        return $resolved;
    }

    /**
     * Stream upload to Google Drive using resumable upload
     */
    private function streamToGoogleDrive(StreamInterface $stream, array $fileInfo, $user, ?string $transferId = null, ?string $folderId = null): string
    {
        Log::info('Starting streaming upload to Google Drive', [
            'filename' => $fileInfo['filename'],
            'size' => $fileInfo['size'],
            'transfer_id' => $transferId,
            'folder_id' => $folderId
        ]);

        $client = $this->getGoogleClient($user);
        $service = new Google_Service_Drive($client);

        $metadata = ['name' => $fileInfo['filename']];

        // Omitted entirely rather than sent as null: Drive reads a missing
        // parents key as "My Drive root", which is the pre-folder behaviour.
        if ($folderId) {
            $metadata['parents'] = [$folderId];
        }

        $fileMetadata = new Google_Service_Drive_DriveFile($metadata);

        // Enable deferred mode for resumable upload
        $client->setDefer(true);

        $request = $service->files->create($fileMetadata, [
            'mimeType' => $fileInfo['mimeType'],
            'uploadType' => 'resumable'
        ]);

        // Create media upload handler
        $media = new \Google_Http_MediaFileUpload(
            $client,
            $request,
            $fileInfo['mimeType'],
            null,
            true,
            $this->chunkSize
        );

        $media->setFileSize($fileInfo['size']);

        // Convert PSR-7 stream to PHP stream resource
        $phpStream = StreamWrapper::getResource($stream);

        $status = false;
        $bytesUploaded = 0;

        // Initialize progress tracking
        if ($transferId) {
            StreamProgressController::updateProgress(
                $transferId,
                0,
                $fileInfo['size'],
                $fileInfo['filename'],
                'transferring'
            );
        }

        // Stream chunks directly from download to upload with proper buffering
        $buffer = '';
        $chunkCount = 0;
        $totalChunks = ceil($fileInfo['size'] / $this->chunkSize);

        Log::info('[STREAMING] Starting chunked upload', [
            'file_size' => $fileInfo['size'],
            'file_size_mb' => round($fileInfo['size'] / 1048576, 2),
            'chunk_size' => $this->chunkSize,
            'chunk_size_mb' => round($this->chunkSize / 1048576, 2),
            'estimated_chunks' => $totalChunks
        ]);

        while (!$status && !feof($phpStream)) {
            // Read data from stream
            $readSize = $this->chunkSize - strlen($buffer);
            $readStartTime = microtime(true);
            $data = fread($phpStream, $readSize);
            $readTime = microtime(true) - $readStartTime;

            if ($data === false) {
                break;
            }

            $buffer .= $data;

            // Check if we have enough data to send
            $isEof = feof($phpStream);
            $bufferSize = strlen($buffer);

            // Send chunk when:
            // 1. Buffer reaches configured chunk size (e.g., 10MB)
            // 2. We're at EOF and have data (final chunk can be any size)
            if ($bufferSize >= $this->chunkSize || ($isEof && $bufferSize > 0)) {
                // Determine what to send
                if (!$isEof && $bufferSize >= $this->chunkSize) {
                    // Send exactly chunk size worth of data (e.g., 10MB)
                    $chunkToSend = substr($buffer, 0, $this->chunkSize);
                    $buffer = substr($buffer, $this->chunkSize);
                } elseif ($isEof && $bufferSize > 0) {
                    // Final chunk - send everything remaining
                    $chunkToSend = $buffer;
                    $buffer = '';
                } else {
                    // Should not happen, but continue buffering if it does
                    continue;
                }

                // Only send if we have data and it meets Google's minimum requirements
                if (strlen($chunkToSend) >= self::GOOGLE_MIN_CHUNK_SIZE || ($isEof && strlen($chunkToSend) > 0)) {
                    $chunkCount++;
                    $uploadStartTime = microtime(true);

                    Log::info('[STREAMING] Sending chunk #' . $chunkCount, [
                        'chunk_number' => $chunkCount,
                        'total_chunks' => $totalChunks,
                        'chunk_size' => strlen($chunkToSend),
                        'chunk_size_mb' => round(strlen($chunkToSend) / 1048576, 2),
                        'is_final' => $isEof && empty($buffer),
                        'buffer_remaining' => strlen($buffer),
                        'configured_chunk_size' => $this->chunkSize,
                        'configured_chunk_size_mb' => round($this->chunkSize / 1048576, 2),
                        'bytes_uploaded_so_far' => $bytesUploaded
                    ]);

                    $status = $media->nextChunk($chunkToSend);
                    $uploadTime = microtime(true) - $uploadStartTime;
                    $bytesUploaded += strlen($chunkToSend);

                    $chunkSpeed = (strlen($chunkToSend) / $uploadTime) / 1048576; // MB/s

                    Log::info('[STREAMING] Chunk uploaded successfully', [
                        'chunk_number' => $chunkCount,
                        'upload_time' => round($uploadTime, 2),
                        'chunk_speed_mbps' => round($chunkSpeed, 2),
                        'overall_progress' => round(($bytesUploaded / $fileInfo['size']) * 100, 2)
                    ]);

                    // Report progress if callback is set
                    if ($this->progressCallback) {
                        call_user_func($this->progressCallback, $bytesUploaded, $fileInfo['size']);
                    }

                    // Update progress for SSE
                    if ($transferId) {
                        StreamProgressController::updateProgress(
                            $transferId,
                            $bytesUploaded,
                            $fileInfo['size'],
                            $fileInfo['filename'],
                            'transferring'
                        );
                    }

                    // Log progress every 5 chunks or on final chunk
                    if ($chunkCount % 5 == 0 || $isEof) {
                        Log::debug('[STREAMING] Upload progress', [
                            'chunks_sent' => $chunkCount,
                            'uploaded_bytes' => $bytesUploaded,
                            'uploaded_mb' => round($bytesUploaded / 1048576, 2),
                            'total_bytes' => $fileInfo['size'],
                            'total_mb' => round($fileInfo['size'] / 1048576, 2),
                            'percentage' => round(($bytesUploaded / $fileInfo['size']) * 100, 2)
                        ]);
                    }
                }
            }
        }

        // Close the stream
        fclose($phpStream);

        $client->setDefer(false);

        if (!$status) {
            if ($transferId) {
                StreamProgressController::completeTransfer($transferId, false);
            }
            throw new \Exception('Upload to Google Drive failed');
        }

        // Mark transfer as completed
        if ($transferId) {
            StreamProgressController::completeTransfer($transferId, true);
        }

        Log::info('[STREAMING] Google Drive upload completed', [
            'file_id' => $status->id,
            'filename' => $status->name,
            'total_chunks' => $chunkCount,
            'total_bytes' => $bytesUploaded,
            'total_mb' => round($bytesUploaded / 1048576, 2)
        ]);

        return $status->id;
    }

    /**
     * Get configured Google Client for user
     */
    /**
     * Public because DriveFolderService needs the same authenticated client,
     * refresh handling included, and a second copy of this would be a second
     * place for token refresh to go wrong.
     */
    public function getGoogleClient($user): Google_Client
    {
        $client = new Google_Client();

        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));

        // Parse and set the token
        $token = is_string($user->google_token) ? json_decode($user->google_token, true) : $user->google_token;
        $client->setAccessToken($token);

        // Refresh token if expired
        if ($client->isAccessTokenExpired()) {
            Log::info('Refreshing expired Google token');

            $refreshToken = $user->google_refresh_token;
            if (!$refreshToken && is_array($token) && isset($token['refresh_token'])) {
                $refreshToken = $token['refresh_token'];
            }

            if (!$refreshToken) {
                throw new \Exception('Your Google Drive session has expired. Please reconnect.');
            }

            $client->fetchAccessTokenWithRefreshToken($refreshToken);
            $newToken = $client->getAccessToken();

            $user->google_token = json_encode($newToken);
            $user->save();

            Log::info('Google token refreshed');
        }

        return $client;
    }
}