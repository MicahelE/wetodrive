<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class TransferController extends Controller
{
    public function index()
    {
        return view('home');
    }

    public function transfer(Request $request)
    {
        set_time_limit(300);

        $request->validate([
            'wetransfer_url' => 'required|url'
        ]);

        if (!Auth::check()) {
            Log::warning('Transfer attempted without Google Drive authentication');
            return redirect()->back()->with('error', 'Please connect to Google Drive first.');
        }

        $user = Auth::user();

        // Check subscription limits
        if (!$this->checkTransferLimits($user)) {
            Log::warning('Transfer attempted but user exceeded limits', [
                'user_id' => $user->id,
                'subscription_tier' => $user->subscription_tier
            ]);

            return redirect()->back()->with('error',
                'You have reached your transfer limit for this month. ' .
                '<a href="' . route('subscription.pricing') . '" style="color: #4285f4; text-decoration: underline;">Upgrade your plan</a> to continue transferring files.'
            );
        }

        try {
            $wetransferUrl = $request->wetransfer_url;
            Log::info('Starting WeTransfer download process', ['url' => $wetransferUrl]);
            
            $downloadUrl = $this->parseWeTransferUrl($wetransferUrl);
            Log::info('Parsed download URL', ['download_url' => $downloadUrl]);
            
            $fileInfo = $this->downloadFile($downloadUrl);
            Log::info('File downloaded to disk', [
                'filename' => $fileInfo['filename'],
                'size' => $fileInfo['size'],
                'mimeType' => $fileInfo['mimeType'],
                'temp_file' => $fileInfo['temp_file']
            ]);

            // Validate file size against subscription limits
            if (!$this->checkFileSizeLimit($user, $fileInfo['size'])) {
                // Cleanup temp file
                if (file_exists($fileInfo['temp_file'])) {
                    unlink($fileInfo['temp_file']);
                }

                $maxSize = $this->getMaxFileSizeForUser($user);
                return redirect()->back()->with('error',
                    'File size (' . $this->formatFileSize($fileInfo['size']) . ') exceeds your plan limit of ' . $this->formatFileSize($maxSize) . '. ' .
                    '<a href="' . route('subscription.pricing') . '" style="color: #4285f4; text-decoration: underline;">Upgrade your plan</a> for larger files.'
                );
            }

            $this->uploadToGoogleDrive($fileInfo, $user);
            Log::info('File uploaded to Google Drive successfully', ['filename' => $fileInfo['filename']]);

            // Increment transfer count after successful upload
            $user->incrementTransferCount();

            return redirect()->back()->with('success', 'File transferred to Google Drive successfully!');
        } catch (\Exception $e) {
            Log::error('Transfer failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Cleanup any temp files that might have been created
            if (isset($fileInfo['temp_file']) && file_exists($fileInfo['temp_file'])) {
                unlink($fileInfo['temp_file']);
                Log::info('Cleaned up temp file after error', ['temp_file' => $fileInfo['temp_file']]);
            }
            
            return redirect()->back()->with('error', 'Transfer failed: ' . $e->getMessage());
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
        
        // Extract transfer ID and security hash from URL
        // Pattern: https://wetransfer.com/downloads/{transfer_id}/{security_hash}
        preg_match('/wetransfer\.com\/downloads\/([a-f0-9]+)\/([a-f0-9]+)/', $pageUrl, $matches);
        
        if (count($matches) < 3) {
            Log::error('Could not extract transfer ID from URL', ['url' => $pageUrl]);
            throw new \Exception('Invalid WeTransfer URL format');
        }
        
        $transferId = $matches[1];
        $securityHash = $matches[2];
        
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
    
    private function downloadFile($url)
    {
        Log::info('Starting file download', ['url' => $url]);
        
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
        
        $client = new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ],
            'allow_redirects' => true,
            'timeout' => 0, // No timeout for streaming downloads
            'read_timeout' => 0,
            'connect_timeout' => 30
        ]);
        
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
            
            // Now stream the actual file download
            $resource = fopen($tempFile, 'w');
            if (!$resource) {
                throw new \Exception('Could not create temporary file for download');
            }
            
            Log::info('Starting streaming download', ['expected_size' => $contentLength]);
            
            $response = $client->get($url, [
                'sink' => $resource
            ]);
            
            fclose($resource);
            
            $actualSize = filesize($tempFile);
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

    private function uploadToGoogleDrive($fileInfo, $user)
    {
        Log::info('Starting Google Drive upload', [
            'filename' => $fileInfo['filename'],
            'size' => $fileInfo['size'],
            'temp_file' => $fileInfo['temp_file']
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

            $fileMetadata = new Google_Service_Drive_DriveFile([
                'name' => $fileInfo['filename']
            ]);
            
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
                
                while (!$status && !feof($handle)) {
                    $chunk = fread($handle, 1024 * 1024); // 1MB chunks
                    $status = $media->nextChunk($chunk);
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
            }
            
            Log::info('Google Drive upload successful', [
                'file_id' => $result->id,
                'filename' => $result->name
            ]);
            
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

    private function checkFileSizeLimit($user, int $fileSize): bool
    {
        $maxSize = $this->getMaxFileSizeForUser($user);
        return $fileSize <= $maxSize;
    }

    private function getMaxFileSizeForUser($user): int
    {
        if ($user->hasActiveSubscription()) {
            return $user->activeSubscription->subscriptionPlan->max_file_size;
        }

        // Free tier: 100MB
        return 100 * 1024 * 1024;
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
