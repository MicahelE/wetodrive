<?php

namespace App\Console\Commands;

use App\Mail\TransferCompleteMail;
use App\Models\Transfer;
use App\Models\User;
use App\Services\ResumableDownloader;
use App\Services\StreamTransferService;
use Google_Client;
use Google_Http_MediaFileUpload;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * One-off recovery: completes a WeTransfer -> Google Drive transfer on a user's
 * behalf, server-side, using the resumable downloader. Built for transfers that
 * failed on the old one-shot path (cURL 18 at the 10-minute token expiry) so a
 * customer doesn't have to retry by hand. Uses the user's stored Google token.
 */
class RecoverTransfer extends Command
{
    protected $signature = 'transfer:recover
        {user : The user id to deliver the file to}
        {url : The original WeTransfer URL (we.tl or wetransfer.com/downloads/...)}
        {--no-email : Do not send the transfer-complete email}
        {--no-count : Do not increment the user\'s transfer count}';

    protected $description = 'Recover a failed WeTransfer transfer into a user\'s Google Drive (resumable).';

    // 8MB upload chunks (a multiple of Google's 256KB requirement).
    private const UPLOAD_CHUNK = 8 * 1024 * 1024;

    public function handle(): int
    {
        @set_time_limit(0);

        $user = User::find($this->argument('user'));
        if (! $user) {
            $this->error("User {$this->argument('user')} not found.");
            return self::FAILURE;
        }

        if (empty($user->google_token)) {
            $this->error("User #{$user->id} ({$user->email}) has no Google token on file.");
            return self::FAILURE;
        }

        $url = $this->argument('url');
        $this->info("Recovering transfer for user #{$user->id} ({$user->email})");

        $svc = new StreamTransferService();
        $tempFile = null;

        try {
            // 1. Mint a fresh direct link, and a refresher to re-mint on resume.
            $directUrl = $svc->parseWeTransferUrl($url);
            $refreshUrl = fn () => $svc->parseWeTransferUrl($url);

            // 2. Probe the file (size, type, name, Range support).
            $client = new Client([
                'headers' => ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'],
                'allow_redirects' => true,
                'connect_timeout' => 30,
            ]);
            $head = $client->head($directUrl);

            $mimeType = $head->getHeaderLine('content-type') ?: 'application/octet-stream';
            if (stripos($mimeType, 'text/html') !== false) {
                throw new \RuntimeException('The link returns a webpage, not a file — it has likely expired.');
            }

            $contentLength = $head->getHeaderLine('content-length');
            $expectedSize = ctype_digit($contentLength) ? (int) $contentLength : null;
            $acceptRanges = stripos($head->getHeaderLine('accept-ranges'), 'bytes') !== false;
            $filename = $this->extractFilename($head->getHeaderLine('content-disposition'), $directUrl);

            $this->line(sprintf(
                '  file: %s  |  size: %s  |  resumable: %s',
                $filename,
                $expectedSize !== null ? $this->formatSize($expectedSize) : 'unknown',
                $acceptRanges ? 'yes' : 'no (full-retry fallback)'
            ));

            // 3. Download to disk with resume.
            $tempFile = storage_path('temp/recover_' . uniqid('', true) . '.tmp');
            $this->ensureDir(dirname($tempFile));

            $this->info('Downloading from WeTransfer...');
            $bar = $expectedSize ? $this->output->createProgressBar($expectedSize) : null;
            $bar?->start();

            $downloader = new ResumableDownloader();
            $bytes = $downloader->download(
                $tempFile,
                $directUrl,
                $expectedSize,
                $refreshUrl,
                $bar ? function ($downloaded, $total) use ($bar) {
                    $bar->setProgress(min($downloaded, $bar->getMaxSteps()));
                } : null,
                $acceptRanges
            );

            $bar?->finish();
            $this->newLine();
            $this->info('  downloaded ' . $this->formatSize($bytes));

            // 4. Upload to the user's Google Drive.
            $this->info('Uploading to Google Drive...');
            $driveId = $this->uploadToDrive($user, $tempFile, $filename, $mimeType, $bytes);
            $driveUrl = "https://drive.google.com/file/d/{$driveId}/view";
            $this->info("  uploaded → {$driveUrl}");

            // 5. Record it.
            if (! $this->option('no-count')) {
                $user->incrementTransferCount();
            }
            Transfer::create([
                'user_id' => $user->id,
                'file_size' => $bytes,
                'transferred_at' => now(),
            ]);

            if (! $this->option('no-email')) {
                try {
                    Mail::to($user)->send(new TransferCompleteMail($user, $filename, $this->formatSize($bytes), $driveUrl));
                    $this->info('  completion email sent');
                } catch (\Throwable $mailEx) {
                    $this->warn('  email failed: ' . $mailEx->getMessage());
                }
            }

            Log::info('transfer:recover completed', [
                'user_id' => $user->id,
                'filename' => $filename,
                'bytes' => $bytes,
                'drive_id' => $driveId,
            ]);

            $this->info('Done.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Recovery failed: ' . $e->getMessage());
            Log::error('transfer:recover failed', [
                'user_id' => $user->id,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return self::FAILURE;
        } finally {
            if ($tempFile && is_file($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    private function extractFilename(string $contentDisposition, string $url): string
    {
        if ($contentDisposition && preg_match('/filename[^;=\n]*=([\'"]?)([^;\n]*)\1/', $contentDisposition, $m)) {
            $name = trim($m[2], '"\'');
            if ($name !== '') {
                return $name;
            }
        }

        $name = urldecode(basename(parse_url($url, PHP_URL_PATH) ?: ''));

        return $name !== '' ? $name : 'recovered_file';
    }

    private function ensureDir(string $dir): void
    {
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Upload a local file to the user's Google Drive. Mirrors
     * TransferController::uploadToGoogleDrive (resumable, chunked) but takes
     * explicit params so it can run outside an HTTP request.
     */
    private function uploadToDrive(User $user, string $tempFile, string $filename, string $mimeType, int $size): string
    {
        $client = new Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));

        $token = is_string($user->google_token) ? json_decode($user->google_token, true) : $user->google_token;
        $client->setAccessToken(is_array($token) ? $token : $user->google_token);

        if ($client->isAccessTokenExpired()) {
            $refreshToken = $user->google_refresh_token
                ?: (is_array($token) ? ($token['refresh_token'] ?? null) : null);

            if (! $refreshToken) {
                throw new \RuntimeException('No Google refresh token — user must reconnect Google Drive.');
            }

            $client->fetchAccessTokenWithRefreshToken($refreshToken);
            $user->google_token = json_encode($client->getAccessToken());
            $user->save();
            $this->line('  (refreshed Google token)');
        }

        $service = new Google_Service_Drive($client);
        $metadata = new Google_Service_Drive_DriveFile(['name' => $filename]);

        $client->setDefer(true);
        $request = $service->files->create($metadata, ['mimeType' => $mimeType, 'uploadType' => 'resumable']);

        $media = new Google_Http_MediaFileUpload($client, $request, $mimeType, null, true, self::UPLOAD_CHUNK);
        $media->setFileSize($size);

        $bar = $this->output->createProgressBar($size);
        $bar->start();

        $status = false;
        $handle = fopen($tempFile, 'rb');
        try {
            while (! $status && ! feof($handle)) {
                $chunk = fread($handle, self::UPLOAD_CHUNK);
                $status = $media->nextChunk($chunk);
                $bar->advance(strlen($chunk));
            }
        } finally {
            fclose($handle);
            $client->setDefer(false);
        }

        $bar->finish();
        $this->newLine();

        if (! $status || empty($status->id)) {
            throw new \RuntimeException('Google Drive upload did not return a file id.');
        }

        return $status->id;
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 2) . 'GB';
        }
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1) . 'MB';
        }

        return round($bytes / 1024, 1) . 'KB';
    }
}
