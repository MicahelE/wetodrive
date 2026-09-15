<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Mail\WelcomeMail;
use App\Models\User;
use App\Services\GeoLocationService;
use Illuminate\Support\Facades\Mail;
use App\Services\DropboxService;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function redirectToGoogle(Request $request)
    {
        // Someone arriving from a share link has to come back to it after
        // consent, or they sign in and lose the transfer they were sent.
        if ($token = $request->query('share')) {
            session(['pending_share' => $token]);
        }

        return Socialite::driver('google')
            ->scopes(['https://www.googleapis.com/auth/drive.file'])
            ->with(['access_type' => 'offline', 'prompt' => 'consent'])
            ->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            Log::info('Google callback received', [
                'has_token' => !empty($googleUser->token),
                'has_refresh_token' => !empty($googleUser->refreshToken),
                'email' => $googleUser->getEmail()
            ]);

            // Store the full token response
            $tokenData = [
                'access_token' => $googleUser->token,
                'refresh_token' => $googleUser->refreshToken,
                'expires_in' => $googleUser->expiresIn,
            ];

            if (empty($googleUser->refreshToken)) {
                Log::warning('No refresh token received from Google. User may need to revoke access and re-authenticate.');
            }

            // What Google actually granted. Socialite fills approvedScopes from
            // the token response; the callback's own ?scope= is the fallback,
            // because a missing scope must never be mistaken for a granted one.
            $granted = implode(' ', (array) ($googleUser->approvedScopes ?: []))
                ?: (string) $request->query('scope');

            $user = User::updateOrCreate([
                'email' => $googleUser->getEmail(),
            ], [
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
                'google_token' => json_encode($tokenData),
                'google_refresh_token' => $googleUser->refreshToken,
                'google_scopes' => $granted ?: null,
            ]);

            Log::info('User authenticated and saved', [
                'user_id' => $user->id,
                'has_refresh_token_saved' => !empty($user->google_refresh_token)
            ]);

            $this->completeSignIn($request, $user);

            // Someone signing in for the Dropbox page never needs Drive, so the
            // missing-scope warning would only send them the wrong way.
            $forDropbox = session('url.intended') === route('dropbox');

            if (! $user->hasDriveAccess() && ! $forDropbox) {
                // Drive is a tick-box on Google's consent screen and is easily
                // clicked past. Say so now: the alternative is letting them
                // start a transfer that downloads everything and is then
                // refused by Drive, which is how #603 lost 2.5GB and 35 minutes.
                Log::warning('Sign-in granted no Drive scope', [
                    'user_id' => $user->id,
                    'granted' => $granted,
                ]);

                return redirect()->route('home')->with(
                    'drive_permission_missing',
                    'Almost there. Google did not grant access to your Drive, so we cannot deliver files yet. Reconnect and tick "See, edit, create and delete only the specific Google Drive files you use with this app".'
                );
            }

            if ($token = session()->pull('pending_share')) {
                return redirect()->route('shares.show', $token);
            }

            return redirect()->intended(route('home'))->with('success', $forDropbox
                ? 'Signed in. Now connect your Dropbox.'
                : 'Connected to Google Drive successfully!');
        } catch (\Exception $e) {
            return redirect()->route('home')->with('error', 'Failed to connect to Google Drive: ' . $e->getMessage());
        }
    }

    public function disconnect()
    {
        $dropboxOnly = Auth::user()?->isDropboxOnly() ?? false;

        if (Auth::check()) {
            Auth::logout();
            Log::info('User disconnected from Google Drive');
        }

        // Someone who only ever used Dropbox has no Drive to "reconnect", and
        // the page they came from is the Dropbox one.
        if ($dropboxOnly) {
            return redirect()->route('dropbox')->with('success', 'Signed out.');
        }

        return redirect()->route('home')->with('success', 'Disconnected from Google Drive. Please reconnect to continue.');
    }

    /** Signed in, this connects Dropbox to the account. Signed out, it is the sign-in. */
    public function redirectToDropbox(Request $request)
    {
        $state = Str::random(40);
        $request->session()->put('dropbox_state', $state);

        // ?switch=1 is "Use a different Dropbox account". Only then, because it
        // signs the browser out of dropbox.com, which nobody wants on every sign-in.
        return redirect()->away(DropboxService::authorizeUrl($state, $request->boolean('switch')));
    }

    public function handleDropboxCallback(Request $request)
    {
        // The state ties the callback to a redirect this browser started. Without
        // it, anyone could link their own Dropbox to someone else's account and
        // collect that person's transfers.
        $expected = $request->session()->pull('dropbox_state');

        if (! $expected || ! hash_equals($expected, (string) $request->query('state'))) {
            Log::warning('Dropbox callback with a state we did not issue', ['user_id' => $request->user()?->id]);

            return redirect()->route('dropbox')->with('error', 'Could not connect Dropbox. Please try again.');
        }

        // Cancel on Dropbox's consent screen arrives as ?error=access_denied.
        if ($request->filled('error') || ! $request->filled('code')) {
            return redirect()->route('dropbox')->with('error', 'Dropbox was not connected.');
        }

        try {
            $tokens = DropboxService::exchangeCode((string) $request->query('code'));
        } catch (\Throwable $e) {
            Log::error('Dropbox code exchange failed', ['user_id' => $request->user()?->id, 'error' => $e->getMessage()]);

            return redirect()->route('dropbox')->with('error', 'Could not connect Dropbox. Please try again.');
        }

        // Without a refresh token the connection dies after four hours, possibly
        // mid-transfer. Without an account id there is nothing to sign in as.
        if (empty($tokens['refresh_token']) || empty($tokens['account_id'])) {
            Log::error('Dropbox token response incomplete', [
                'user_id' => $request->user()?->id,
                'has_refresh_token' => ! empty($tokens['refresh_token']),
                'has_account_id' => ! empty($tokens['account_id']),
            ]);

            return redirect()->route('dropbox')->with('error', 'Could not connect Dropbox. Please try again.');
        }

        $signingIn = ! Auth::check();

        if ($signingIn) {
            try {
                $user = $this->userForDropbox(DropboxService::currentAccount($tokens['access_token']));
            } catch (\Throwable $e) {
                Log::error('Dropbox account lookup failed', ['error' => $e->getMessage()]);

                return redirect()->route('dropbox')->with('error', 'Could not sign in with Dropbox. Please try again.');
            }

            if (! $user) {
                return redirect()->route('dropbox')->with('error', 'Dropbox has not verified the email address on that account yet. Verify it in your Dropbox settings, then try again.');
            }
        } else {
            $user = $request->user();
        }

        // One Dropbox belongs to one WetoDrive account. Left linked to two, a
        // Dropbox sign-in would land in whichever of them was found first.
        User::where('dropbox_account_id', $tokens['account_id'])
            ->whereKeyNot($user->id)
            ->update(['dropbox_account_id' => null, 'dropbox_refresh_token' => null]);

        $user->forceFill([
            'dropbox_account_id' => $tokens['account_id'],
            'dropbox_refresh_token' => $tokens['refresh_token'],
        ])->save();
        DropboxService::for($user)->forgetAccessToken();

        if ($signingIn) {
            $this->completeSignIn($request, $user);

            Log::info('Signed in with Dropbox', ['user_id' => $user->id, 'new_account' => $user->wasRecentlyCreated]);

            return redirect()->route('dropbox')->with('success', $user->wasRecentlyCreated
                ? 'Welcome to WetoDrive. Paste a WeTransfer link to start.'
                : 'Signed in with Dropbox. Paste a WeTransfer link to start.');
        }

        Log::info('Dropbox connected', ['user_id' => $user->id]);

        return redirect()->route('dropbox')->with('success', 'Dropbox connected. Paste a WeTransfer link to start.');
    }

    public function disconnectDropbox(Request $request)
    {
        $user = $request->user();

        try {
            DropboxService::for($user)->revoke();
        } catch (\Throwable $e) {
            // Already revoked on their side, or Dropbox is down. Forgotten here either way.
            Log::info('Dropbox revoke failed on disconnect', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        $user->forceFill(['dropbox_account_id' => null, 'dropbox_refresh_token' => null])->save();

        return redirect()->route('dropbox')->with('success', 'Dropbox disconnected.');
    }

    /**
     * The WetoDrive account a Dropbox sign-in belongs to: the one this Dropbox
     * is already linked to, else the account with its email, else a new one.
     *
     * Null when Dropbox has not verified the email. Anyone can type any address
     * into a Dropbox account, so matching on it would hand over someone else's
     * account and plan, and creating one would squat an address whose owner
     * later signs in with Google and lands in the squatter's account.
     */
    private function userForDropbox(array $account): ?User
    {
        if ($linked = User::where('dropbox_account_id', $account['account_id'])->first()) {
            return $linked;
        }

        if (! ($account['email_verified'] ?? false) || blank($account['email'] ?? null)) {
            return null;
        }

        return User::firstOrCreate(
            ['email' => Str::lower($account['email'])],
            [
                'name' => $account['name']['display_name'] ?? $account['email'],
                // The column is required, but nobody ever signs in with a password.
                'password' => Str::random(40),
            ],
        );
    }

    /** What every sign-in does, whichever service it came through. */
    private function completeSignIn(Request $request, User $user): void
    {
        if ($user->wasRecentlyCreated) {
            // Only on creation. A sign-in also finds existing accounts, and
            // writing then would overwrite the real first touch with our own domain.
            if ($source = $request->session()->get('signup_source')) {
                $user->forceFill([
                    'signup_referrer' => $source['referrer'] ?? null,
                    'signup_landing' => $source['landing'] ?? null,
                ])->save();
            }

            Mail::to($user)->send(new WelcomeMail($user));
        }

        // Detect country if not already set
        if (empty($user->country_code)) {
            $geoService = new GeoLocationService();
            $countryCode = $geoService->getCountryFromRequest($request);
            if ($countryCode) {
                $user->country_code = $countryCode;
                $user->save();
                Log::info('Country detected during signup', [
                    'user_id' => $user->id,
                    'country' => $countryCode
                ]);
            }
        }

        Auth::login($user);
    }
}
