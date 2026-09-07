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

            $user = User::updateOrCreate([
                'email' => $googleUser->getEmail(),
            ], [
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
                'google_token' => json_encode($tokenData),
                'google_refresh_token' => $googleUser->refreshToken,
            ]);

            // Send welcome email to new users
            if ($user->wasRecentlyCreated) {
                // Only on creation. updateOrCreate above also fires on every
                // later sign-in, and writing then would overwrite the real first
                // touch with our own domain.
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

            Log::info('User authenticated and saved', [
                'user_id' => $user->id,
                'has_refresh_token_saved' => !empty($user->google_refresh_token)
            ]);

            Auth::login($user);

            if ($token = session()->pull('pending_share')) {
                return redirect()->route('shares.show', $token);
            }

            return redirect()->route('home')->with('success', 'Connected to Google Drive successfully!');
        } catch (\Exception $e) {
            return redirect()->route('home')->with('error', 'Failed to connect to Google Drive: ' . $e->getMessage());
        }
    }
    
    public function disconnect()
    {
        if (Auth::check()) {
            Auth::logout();
            Log::info('User disconnected from Google Drive');
        }
        
        return redirect()->route('home')->with('success', 'Disconnected from Google Drive. Please reconnect to continue.');
    }
}
