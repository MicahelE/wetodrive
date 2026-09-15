<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Dropbox as a destination: connecting an account, and a chunked upload that
 * reads from any stream, so the streamed path and the temp-file path share one
 * upload loop.
 *
 * Plain HTTP rather than an SDK. The whole integration is six endpoints.
 */
class DropboxService
{
    private const AUTHORIZE_URL = 'https://www.dropbox.com/oauth2/authorize';
    private const TOKEN_URL = 'https://api.dropboxapi.com/oauth2/token';
    private const API = 'https://api.dropboxapi.com/2/';
    private const CONTENT = 'https://content.dropboxapi.com/2/';

    // Dropbox asks for multiples of 4MB and caps a request at 150MB. 8MB keeps
    // a chunk comfortably inside the 512M memory limit a transfer runs with.
    public const CHUNK_SIZE = 8 * 1024 * 1024;

    public function __construct(private User $user) {}

    public static function for(User $user): self
    {
        return new self($user);
    }

    /**
     * $chooseAccount makes Dropbox sign the browser out and ask which account
     * to use. Without it, a browser already signed in to Dropbox is sent
     * straight back as that same account, so signing out of WetoDrive and
     * clicking again can never reach a second Dropbox.
     */
    public static function authorizeUrl(string $state, bool $chooseAccount = false): string
    {
        return self::AUTHORIZE_URL . '?' . http_build_query([
            'client_id' => config('services.dropbox.client_id'),
            'redirect_uri' => config('services.dropbox.redirect'),
            'response_type' => 'code',
            // offline is what returns a refresh token. Without one the access
            // token dies after four hours, part way through a large transfer.
            'token_access_type' => 'offline',
            'state' => $state,
        ] + ($chooseAccount ? ['force_reauthentication' => 'true'] : []));
    }

    /** Swap the callback's code for Dropbox's token response. */
    public static function exchangeCode(string $code): array
    {
        return self::tokenRequest([
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => config('services.dropbox.redirect'),
        ])->throw()->json();
    }

    /** Whose Dropbox a fresh access token opens: account_id, name, email and email_verified. */
    public static function currentAccount(string $accessToken): array
    {
        return Http::withToken($accessToken)
            ->withBody('null', 'application/json')
            ->post(self::API . 'users/get_current_account')
            ->throw()
            ->json();
    }

    /**
     * '/Clients/Acme/Selects/take.mov' from any mix of folder, sub-path and
     * name, where any part may be empty or carry its own slashes.
     */
    public static function path(?string ...$parts): string
    {
        $parts = array_map(fn ($part) => trim(str_replace('\\', '/', (string) $part), '/'), $parts);

        return '/' . implode('/', array_filter($parts, 'strlen'));
    }

    /** The dropbox.com page for a folder path; the top of their Dropbox when null. */
    public static function webUrl(?string $path): string
    {
        return 'https://www.dropbox.com/home' . implode('/', array_map('rawurlencode', explode('/', (string) $path)));
    }

    public function accessToken(bool $fresh = false): string
    {
        if (blank($this->user->dropbox_refresh_token)) {
            throw new \RuntimeException('Dropbox is not connected. Connect Dropbox and try again.');
        }

        $key = "dropbox.access_token.{$this->user->id}";

        if ($fresh) {
            Cache::forget($key);
        }

        // Tokens last four hours. Kept for three, so a long upload is never
        // started on one with minutes left.
        return Cache::remember($key, now()->addHours(3), function () {
            $response = self::tokenRequest([
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->user->dropbox_refresh_token,
            ]);

            if ($response->failed()) {
                Log::warning('Dropbox token refresh failed', [
                    'user_id' => $this->user->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \RuntimeException('Your Dropbox connection has expired. Reconnect Dropbox and try again.');
            }

            return $response->json('access_token');
        });
    }

    /** Bytes left in their Dropbox, or null when Dropbox does not say. */
    public function freeSpace(): ?int
    {
        $usage = $this->rpc('users/get_space_usage')->json();
        $allocated = $usage['allocation']['allocated'] ?? null;

        if ($allocated === null) {
            return null;
        }

        // A team's allocation is shared, and its own "used" is what counts against it.
        return max(0, $allocated - ($usage['allocation']['used'] ?? $usage['used'] ?? 0));
    }

    public function revoke(): void
    {
        try {
            $this->rpc('auth/token/revoke');
        } finally {
            $this->forgetAccessToken();
        }
    }

    /**
     * Call on any change of connection. A cached token keeps writing to the
     * previous account for up to three hours.
     */
    public function forgetAccessToken(): void
    {
        Cache::forget("dropbox.access_token.{$this->user->id}");
    }

    /**
     * Upload everything readable from $stream to $path.
     *
     * An upload session rather than /files/upload, which stops at 150MB. The
     * file only appears in Dropbox when the session is finished, so a transfer
     * that dies part way leaves nothing half-written in their Dropbox.
     *
     * @param  resource  $stream
     * @param  callable|null  $onProgress  fn (int $uploaded, int $total)
     * @param  int  $expectedSize  when known, a shorter stream is refused rather than committed
     * @return array the new file's metadata; path_display is where it landed
     */
    public function upload($stream, string $path, ?callable $onProgress = null, int $expectedSize = 0): array
    {
        $sessionId = $this->content('files/upload_session/start', ['close' => false])->throw()->json('session_id');
        $offset = 0;

        while (! feof($stream)) {
            $chunk = stream_get_contents($stream, self::CHUNK_SIZE);

            if ($chunk === false) {
                throw new \RuntimeException('The download stream failed part way through.');
            }

            if ($chunk === '') {
                break;
            }

            $this->append($sessionId, $offset, $chunk);
            $offset += strlen($chunk);

            if ($onProgress) {
                $onProgress($offset, $expectedSize ?: $offset);
            }
        }

        // A dropped WeTransfer connection reads as a clean end of stream.
        // Finishing anyway would put a truncated file in their Dropbox that
        // looks exactly like a delivered one.
        if ($expectedSize > 0 && $offset !== $expectedSize) {
            throw new \RuntimeException("The download ended early ({$offset} of {$expectedSize} bytes), so nothing was saved.");
        }

        $response = $this->content('files/upload_session/finish', [
            'cursor' => ['session_id' => $sessionId, 'offset' => $offset],
            'commit' => ['path' => $path, 'mode' => 'add', 'autorename' => true],
        ]);

        if (str_contains((string) $response->json('error_summary'), 'insufficient_space')) {
            throw new \RuntimeException('Your Dropbox is full, so the file could not be saved.');
        }

        return $response->throw()->json();
    }

    private function append(string $sessionId, int $offset, string $chunk): void
    {
        $response = $this->content('files/upload_session/append_v2', [
            'cursor' => ['session_id' => $sessionId, 'offset' => $offset],
        ], $chunk);

        // A retried chunk that Dropbox had in fact already taken comes back as
        // incorrect_offset, pointing just past it. That chunk is in, so carry on.
        if ($response->status() === 409 && $response->json('error.correct_offset') === $offset + strlen($chunk)) {
            return;
        }

        $response->throw();
    }

    private function rpc(string $endpoint): Response
    {
        // Dropbox wants a literal "null" body for endpoints that take no arguments.
        return $this->request()->withBody('null', 'application/json')->post(self::API . $endpoint)->throw();
    }

    private function content(string $endpoint, array $arg, string $body = ''): Response
    {
        return $this->request()
            // json_encode escapes non-ASCII by default, which this header requires:
            // a filename like "résumé.pdf" would otherwise be rejected.
            ->withHeaders(['Dropbox-API-Arg' => json_encode($arg)])
            ->withBody($body, 'application/octet-stream')
            ->post(self::CONTENT . $endpoint);
    }

    private function request(): PendingRequest
    {
        return Http::withToken($this->accessToken())
            ->timeout(600)
            ->retry(
                5,
                function (int $attempt, \Throwable $e) {
                    $after = $e instanceof RequestException ? (int) $e->response->header('Retry-After') : 0;

                    return $after > 0 ? $after * 1000 : $attempt * 2000;
                },
                function (\Throwable $e, PendingRequest $request) {
                    if ($e instanceof ConnectionException) {
                        return true;
                    }

                    if (! $e instanceof RequestException) {
                        return false;
                    }

                    // An hours-long transfer can outlive its token; swap in a new one.
                    if ($e->response->status() === 401) {
                        $request->withToken($this->accessToken(fresh: true));

                        return true;
                    }

                    return $e->response->status() === 429 || $e->response->serverError();
                },
                throw: false,
            );
    }

    private static function tokenRequest(array $form): Response
    {
        return Http::asForm()
            ->withBasicAuth((string) config('services.dropbox.client_id'), (string) config('services.dropbox.client_secret'))
            ->post(self::TOKEN_URL, $form);
    }
}
