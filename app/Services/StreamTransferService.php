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

    public function __construct(int $chunkSize = null)
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
    public function streamTransfer(string $downloadUrl, array $fileInfo, $user, string $transferId = null): string
    {
        Log::info('Starting streaming transfer', [
            'download_url' => $downloadUrl,
            'filename' => $fileInfo['filename'],
            'transfer_id' => $transferId
        ]);

        // Get WeTransfer download stream
        $downloadStream = $this->getWeTransferStream($downloadUrl, $fileInfo);

        // Upload stream to Google Drive with progress tracking
        $fileId = $this->streamToGoogleDrive($downloadStream, $fileInfo, $user, $transferId);

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
     * Get direct download link from WeTransfer page
     */
    private function getDirectDownloadLink(string $pageUrl): string
    {
        Log::info('Getting direct download link', ['page_url' => $pageUrl]);

        // Extract transfer ID and security hash
        preg_match('/wetransfer\.com\/downloads\/([a-f0-9]+)\/([a-f0-9]+)/', $pageUrl, $matches);

        if (count($matches) < 3) {
            throw new \Exception('Invalid WeTransfer URL format');
        }

        $transferId = $matches[1];
        $securityHash = $matches[2];

        $cookieJar = new CookieJar();

        // Fetch the page to get CSRF token
        $client = new Client([
            'cookies' => $cookieJar,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
            ]
        ]);

        $pageResponse = $client->get($pageUrl);
        $html = $pageResponse->getBody()->getContents();

        // Extract CSRF token
        $csrfToken = null;
        if (preg_match('/name="csrf-token" content="([^"]+)"/', $html, $csrfMatches)) {
            $csrfToken = $csrfMatches[1];
        } elseif (preg_match('/"csrf_token":"([^"]+)"/', $html, $csrfMatches)) {
            $csrfToken = $csrfMatches[1];
        }

        // Make API request to get download link
        $apiUrl = "https://wetransfer.com/api/v4/transfers/{$transferId}/download";

        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Origin' => 'https://wetransfer.com',
            'Referer' => $pageUrl
        ];

        if ($csrfToken) {
            $headers['X-CSRF-Token'] = $csrfToken;
        }

        $requestBody = [
            'security_hash' => $securityHash,
            'intent' => 'entire_transfer'
        ];

        if ($csrfToken) {
            $requestBody['csrf_token'] = $csrfToken;
        }

        try {
            $apiResponse = $client->post($apiUrl, [
                'json' => $requestBody,
                'headers' => $headers
            ]);

            $responseData = json_decode($apiResponse->getBody()->getContents(), true);

            // Check for download URL in response
            $downloadUrl = $responseData['direct_link']
                ?? $responseData['download_url']
                ?? $responseData['fields']['download_url']
                ?? $responseData['presigned_url']
                ?? null;

            if ($downloadUrl) {
                Log::info('Got direct download URL', ['url' => $downloadUrl]);
                return $downloadUrl;
            }

            // Fallback URL
            $fallbackUrl = "https://download.wetransfer.com/eugv/{$transferId}/{$securityHash}";
            Log::info('Using fallback URL', ['url' => $fallbackUrl]);

            return $fallbackUrl;

        } catch (\Exception $e) {
            Log::error('Failed to get direct download link', ['error' => $e->getMessage()]);

            // Detect specific WeTransfer errors to provide better user feedback
            $errorMessage = $e->getMessage();
            if (str_contains($errorMessage, 'No download access') ||
                str_contains($errorMessage, '404') ||
                str_contains($errorMessage, 'expired') ||
                str_contains($errorMessage, 'not found')) {
                throw new \Exception('WETRANSFER_EXPIRED:' . $errorMessage);
            }

            throw new \Exception('Failed to get download link from WeTransfer');
        }
    }

    /**
     * Stream upload to Google Drive using resumable upload
     */
    private function streamToGoogleDrive(StreamInterface $stream, array $fileInfo, $user, string $transferId = null): string
    {
        Log::info('Starting streaming upload to Google Drive', [
            'filename' => $fileInfo['filename'],
            'size' => $fileInfo['size'],
            'transfer_id' => $transferId
        ]);

        $client = $this->getGoogleClient($user);
        $service = new Google_Service_Drive($client);

        $fileMetadata = new Google_Service_Drive_DriveFile([
            'name' => $fileInfo['filename']
        ]);

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
    protected function getGoogleClient($user): Google_Client
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