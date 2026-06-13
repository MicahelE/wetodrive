<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * Downloads a URL to a local file, resilient to mid-stream connection drops.
 *
 * WeTransfer's signed download URLs expire ~10 minutes after they are minted
 * (the JWT carries a 600s exp), and the CDN closes the connection the instant
 * the token expires — mid-stream for any file that takes longer than that to
 * pull (cURL error 18: "transfer closed with N bytes remaining to read"). A
 * plain one-shot GET therefore fails near the end of large/slow downloads, and
 * restarting from byte 0 just loses the same 10-minute race again.
 *
 * This downloader resumes from the last byte received using an HTTP Range
 * request, re-minting a fresh signed URL between attempts via the optional
 * $refreshUrl callback, so each segment is comfortably shorter than the token
 * lifetime. When the origin does not advertise Range support it falls back to
 * retrying the whole download from scratch.
 */
class ResumableDownloader
{
    /**
     * The unit of work for a single attempt.
     *
     * @var callable(string $url, ?int $rangeStart, resource $sink, ?callable $onProgress, int $baseOffset): void
     */
    private $transport;

    public function __construct(
        ?callable $transport = null,
        ?Client $client = null,
        private int $maxAttempts = 6,
    ) {
        $this->transport = $transport ?? $this->defaultTransport($client ?? $this->makeClient());
    }

    /**
     * Download $url to $destPath, resuming on premature connection closes.
     *
     * @param  string         $destPath      Local file to write to.
     * @param  string         $url           Initial (already direct) download URL.
     * @param  int|null       $expectedSize  Total size in bytes, if known (from HEAD).
     * @param  callable|null  $refreshUrl    fn(): string — returns a fresh signed URL.
     * @param  callable|null  $onProgress    fn(int $downloaded, int $total): void.
     * @param  bool           $resumable     Whether the origin supports Range requests.
     * @return int            Final number of bytes written.
     *
     * @throws \RuntimeException when the download cannot be completed.
     */
    public function download(
        string $destPath,
        string $url,
        ?int $expectedSize = null,
        ?callable $refreshUrl = null,
        ?callable $onProgress = null,
        bool $resumable = true,
    ): int {
        $offset = 0;
        $lastError = null;

        for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
            // Only resume (append + Range) when we already have bytes AND the
            // origin supports it; otherwise truncate and fetch from the start.
            $resume = $resumable && $offset > 0;

            $sink = @fopen($destPath, $resume ? 'a' : 'wb');
            if ($sink === false) {
                throw new \RuntimeException("Could not open temp file for writing: {$destPath}");
            }

            try {
                ($this->transport)($url, $resume ? $offset : null, $sink, $onProgress, $resume ? $offset : 0);
                $this->closeResource($sink);

                $offset = $this->fileSize($destPath);
                if ($expectedSize === null || $offset >= $expectedSize) {
                    return $offset;
                }

                // No transport error, but the file is short of the expected
                // size — treat it as a premature close and resume next attempt.
                $lastError = new \RuntimeException(
                    "Incomplete download: have {$offset} of {$expectedSize} bytes"
                );
            } catch (\Throwable $e) {
                $this->closeResource($sink);
                $lastError = $e;

                // Keep what landed on disk when we can resume; otherwise discard
                // it (the next attempt truncates) so a non-Range origin can't
                // corrupt the file by stacking a second full body onto a partial.
                $offset = $resumable ? $this->fileSize($destPath) : 0;
            }

            if ($attempt < $this->maxAttempts) {
                Log::warning('Resumable download attempt failed; retrying', [
                    'attempt' => $attempt,
                    'have_bytes' => $offset,
                    'expected_bytes' => $expectedSize,
                    'resumable' => $resumable,
                    'error' => $lastError?->getMessage(),
                ]);

                // Re-mint a fresh signed URL — the previous one has likely expired.
                $url = $this->refresh($refreshUrl, $url);
            }
        }

        throw new \RuntimeException(
            'Download failed after ' . $this->maxAttempts . ' attempt(s): '
                . ($lastError?->getMessage() ?? 'unknown error'),
            0,
            $lastError instanceof \Throwable ? $lastError : null
        );
    }

    private function refresh(?callable $refreshUrl, string $current): string
    {
        if ($refreshUrl === null) {
            return $current;
        }

        try {
            $fresh = $refreshUrl();
            if (is_string($fresh) && $fresh !== '') {
                return $fresh;
            }
        } catch (\Throwable $e) {
            Log::warning('Resumable download: URL refresh failed, reusing current URL', [
                'error' => $e->getMessage(),
            ]);
        }

        return $current;
    }

    private function makeClient(): Client
    {
        return new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
            'allow_redirects' => true,
            'timeout' => 0,
            'read_timeout' => 0,
            'connect_timeout' => 30,
        ]);
    }

    private function defaultTransport(Client $client): callable
    {
        return function (string $url, ?int $rangeStart, $sink, ?callable $onProgress, int $baseOffset) use ($client): void {
            $options = ['sink' => $sink];

            if ($rangeStart !== null) {
                $options['headers']['Range'] = "bytes={$rangeStart}-";
            }

            if ($onProgress !== null) {
                // Guzzle reports progress for this segment only; fold in the
                // bytes we already have so the caller sees cumulative totals.
                $options['progress'] = function ($downloadTotal, $downloadedBytes) use ($onProgress, $baseOffset) {
                    if ($downloadTotal > 0) {
                        $onProgress($baseOffset + $downloadedBytes, $baseOffset + $downloadTotal);
                    }
                };
            }

            $client->get($url, $options);
        };
    }

    private function closeResource($resource): void
    {
        if (is_resource($resource)) {
            fclose($resource);
        }
    }

    private function fileSize(string $path): int
    {
        clearstatcache(false, $path);

        return is_file($path) ? (int) filesize($path) : 0;
    }
}
