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
        $request->validate([
            'wetransfer_url' => 'required|url'
        ]);

        if (!Auth::check()) {
            Log::warning('Transfer attempted without Google Drive authentication');
            return redirect()->back()->with('error', 'Please connect to Google Drive first.');
        }

        try {
            $wetransferUrl = $request->wetransfer_url;
            Log::info('Starting WeTransfer download process', ['url' => $wetransferUrl]);
            
            $downloadUrl = $this->parseWeTransferUrl($wetransferUrl);
            Log::info('Parsed download URL', ['download_url' => $downloadUrl]);
            
            $fileInfo = $this->downloadFile($downloadUrl);
            Log::info('File downloaded', [
                'filename' => $fileInfo['filename'],
                'size' => strlen($fileInfo['content']),
                'mimeType' => $fileInfo['mimeType']
            ]);
            
            $this->uploadToGoogleDrive($fileInfo, Auth::user());
            Log::info('File uploaded to Google Drive successfully', ['filename' => $fileInfo['filename']]);
            
            return redirect()->back()->with('success', 'File transferred to Google Drive successfully!');
        } catch (\Exception $e) {
            Log::error('Transfer failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
        
        $client = new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ],
            'allow_redirects' => true,
            'timeout' => 300, // 5 minutes timeout for large files
        ]);
        
        try {
            $response = $client->get($url);
            
            $statusCode = $response->getStatusCode();
            Log::info('Download response received', ['status_code' => $statusCode]);
            
            $contentType = $response->getHeader('content-type')[0] ?? 'application/octet-stream';
            $contentLength = $response->getHeader('content-length')[0] ?? 'unknown';
            
            Log::info('Response headers', [
                'content_type' => $contentType,
                'content_length' => $contentLength
            ]);
            
            // Check if we got HTML instead of a file
            if (strpos($contentType, 'text/html') !== false) {
                Log::error('Received HTML instead of file', [
                    'content_type' => $contentType,
                    'body_preview' => substr($response->getBody()->getContents(), 0, 500)
                ]);
                throw new \Exception('Downloaded content appears to be a webpage instead of a file. The download link may have expired or be invalid.');
            }
            
            $contentDisposition = $response->getHeader('content-disposition')[0] ?? '';
            preg_match('/filename="(.+)"/', $contentDisposition, $matches);
            $filename = $matches[1] ?? 'downloaded_file';
            
            Log::info('Extracted filename', ['filename' => $filename]);
            
            $content = $response->getBody()->getContents();
            Log::info('File content downloaded', ['size_bytes' => strlen($content)]);
            
            return [
                'content' => $content,
                'filename' => $filename,
                'mimeType' => $contentType
            ];
        } catch (\Exception $e) {
            Log::error('File download failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function uploadToGoogleDrive($fileInfo, $user)
    {
        Log::info('Starting Google Drive upload', ['filename' => $fileInfo['filename']]);
        
        try {
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
            
            Log::info('Uploading to Google Drive', [
                'filename' => $fileInfo['filename'],
                'size' => strlen($fileInfo['content']),
                'mimeType' => $fileInfo['mimeType']
            ]);

            $result = $service->files->create($fileMetadata, [
                'data' => $fileInfo['content'],
                'mimeType' => $fileInfo['mimeType'],
                'uploadType' => 'multipart'
            ]);
            
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
        }
    }
}
