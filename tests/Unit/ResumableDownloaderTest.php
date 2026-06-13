<?php

namespace Tests\Unit;

use App\Services\ResumableDownloader;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Tests\TestCase;

class ResumableDownloaderTest extends TestCase
{
    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    private function tempPath(): string
    {
        $path = sys_get_temp_dir() . '/rdl_' . uniqid('', true) . '.tmp';
        $this->tempFiles[] = $path;

        return $path;
    }

    public function test_completes_in_a_single_attempt_on_a_healthy_download(): void
    {
        $calls = 0;
        $transport = function (string $url, ?int $rangeStart, $sink) use (&$calls) {
            $calls++;
            fwrite($sink, 'ABCDEFGHIJ');
        };

        $dest = $this->tempPath();
        $size = (new ResumableDownloader($transport))->download($dest, 'https://x/file', 10);

        $this->assertSame(1, $calls, 'a healthy download needs only one attempt');
        $this->assertSame(10, $size);
        $this->assertSame('ABCDEFGHIJ', file_get_contents($dest));
    }

    public function test_resumes_from_the_last_byte_after_a_midstream_close(): void
    {
        $content = 'ABCDEFGHIJ';
        $seen = [];
        $transport = function (string $url, ?int $rangeStart, $sink) use (&$seen, $content) {
            $seen[] = ['url' => $url, 'rangeStart' => $rangeStart];

            if ($rangeStart === null) {
                // First attempt: write part of the file, then the connection drops.
                fwrite($sink, substr($content, 0, 3));
                throw new \RuntimeException('cURL error 18: transfer closed with 7 bytes remaining to read');
            }

            // Resume attempt: append the remaining bytes from the offset.
            fwrite($sink, substr($content, $rangeStart));
        };

        $refreshed = 0;
        $refreshUrl = function () use (&$refreshed) {
            $refreshed++;

            return "https://fresh/url/{$refreshed}";
        };

        $dest = $this->tempPath();
        $size = (new ResumableDownloader($transport))->download($dest, 'https://origin/file', 10, $refreshUrl);

        $this->assertSame(10, $size);
        $this->assertSame($content, file_get_contents($dest), 'resumed file must be byte-complete, not doubled');
        $this->assertCount(2, $seen);
        $this->assertNull($seen[0]['rangeStart'], 'first attempt fetches from the start');
        $this->assertSame(3, $seen[1]['rangeStart'], 'resume continues from the last byte received');
        $this->assertSame('https://fresh/url/1', $seen[1]['url'], 'resume uses a freshly minted URL');
    }

    public function test_falls_back_to_a_full_retry_when_the_origin_has_no_range_support(): void
    {
        $content = 'ABCDEFGHIJ';
        $seen = [];
        $transport = function (string $url, ?int $rangeStart, $sink) use (&$seen, $content) {
            $seen[] = $rangeStart;

            if (count($seen) === 1) {
                fwrite($sink, substr($content, 0, 6));
                throw new \RuntimeException('cURL error 18: transfer closed with 4 bytes remaining to read');
            }

            fwrite($sink, $content);
        };

        $dest = $this->tempPath();
        // resumable = false → no Range; each retry must truncate and refetch.
        $size = (new ResumableDownloader($transport))->download($dest, 'https://x/file', 10, null, null, false);

        $this->assertSame(10, $size, 'truncate-and-retry must not stack a second body onto the partial');
        $this->assertSame('ABCDEFGHIJ', file_get_contents($dest));
        $this->assertSame([null, null], $seen, 'a non-resumable origin is never sent a Range offset');
    }

    public function test_gives_up_and_throws_after_exhausting_attempts(): void
    {
        $transport = function (string $url, ?int $rangeStart, $sink) {
            fwrite($sink, 'A');
            throw new \RuntimeException('cURL error 18: transfer closed with 9 bytes remaining to read');
        };

        $dest = $this->tempPath();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Download failed after 3 attempt');

        (new ResumableDownloader($transport, null, 3))->download($dest, 'https://x/file', 10);
    }

    public function test_treats_a_short_but_errorless_response_as_incomplete(): void
    {
        $content = 'ABCDEFGHIJ';
        $attempts = 0;
        $transport = function (string $url, ?int $rangeStart, $sink) use (&$attempts, $content) {
            $attempts++;

            if ($attempts === 1) {
                // Clean return, but the body was short of Content-Length.
                fwrite($sink, substr($content, 0, 4));

                return;
            }

            fwrite($sink, substr($content, $rangeStart));
        };

        $dest = $this->tempPath();
        $size = (new ResumableDownloader($transport))->download($dest, 'https://x/file', 10);

        $this->assertSame(10, $size);
        $this->assertSame($content, file_get_contents($dest));
        $this->assertSame(2, $attempts, 'a short, error-free response must still trigger a resume');
    }

    /**
     * End-to-end through a real Guzzle client (custom base handler) to prove the
     * actual Range header, append-to-sink, and URL-refresh wiring — i.e. exactly
     * the cURL-18 production failure being recovered.
     */
    public function test_real_guzzle_transport_sends_range_header_and_appends_on_resume(): void
    {
        $requests = [];
        $handler = function (RequestInterface $request, array $options) use (&$requests) {
            $requests[] = $request;
            $sink = $options['sink'];

            if (count($requests) === 1) {
                fwrite($sink, 'ABCDEF'); // 6 bytes land before the connection drops
                return Create::rejectionFor(new ConnectException(
                    'cURL error 18: transfer closed with 4 bytes remaining to read',
                    $request
                ));
            }

            fwrite($sink, 'GHIJ'); // the Range remainder
            return Create::promiseFor(new Response(206, [], 'GHIJ'));
        };

        $client = new Client(['handler' => HandlerStack::create($handler)]);
        $downloader = new ResumableDownloader(null, $client, 4);

        $dest = $this->tempPath();
        $size = $downloader->download(
            $dest,
            'https://origin.example/file',
            10,
            fn () => 'https://fresh.example/file'
        );

        $this->assertSame(10, $size);
        $this->assertSame('ABCDEFGHIJ', file_get_contents($dest));
        $this->assertCount(2, $requests);
        $this->assertSame('', $requests[0]->getHeaderLine('Range'), 'first attempt sends no Range');
        $this->assertSame('https://origin.example/file', (string) $requests[0]->getUri());
        $this->assertSame('bytes=6-', $requests[1]->getHeaderLine('Range'), 'resume requests bytes from the last received');
        $this->assertSame('https://fresh.example/file', (string) $requests[1]->getUri(), 'resume hits the re-minted URL');
    }
}
