<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoLocationService
{
    public function getCountryFromIp(string $ip): ?string
    {
        // Skip localhost and private IPs
        if ($this->isLocalOrPrivateIp($ip)) {
            return null;
        }

        // Cache the result for 24 hours
        $cacheKey = "geo_location_{$ip}";

        return Cache::remember($cacheKey, 60 * 60 * 24, function () use ($ip) {
            return $this->fetchCountryFromApi($ip);
        });
    }

    public function getCountryFromRequest($request): ?string
    {
        $ip = $this->getRealIpAddress($request);

        if (!$ip) {
            return null;
        }

        return $this->getCountryFromIp($ip);
    }

    private function getRealIpAddress($request): ?string
    {
        // Check for IP from various headers (in order of preference)
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_REAL_IP',            // Nginx proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_CLIENT_IP',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];

        foreach ($ipHeaders as $header) {
            $ip = $request->server($header);

            if ($ip && !empty($ip) && $ip !== 'unknown') {
                // Handle comma-separated IPs (from X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $request->ip();
    }

    private function fetchCountryFromApi(string $ip): ?string
    {
        try {
            // Try multiple geolocation services as fallbacks
            $services = [
                'ipapi' => "http://ip-api.com/json/{$ip}?fields=countryCode",
                'ipgeolocation' => "https://api.ipgeolocation.io/ipgeo?apiKey=&ip={$ip}",
                'ipinfo' => "https://ipinfo.io/{$ip}/json"
            ];

            foreach ($services as $serviceName => $url) {
                try {
                    $response = Http::timeout(5)->get($url);

                    if ($response->successful()) {
                        $data = $response->json();
                        $countryCode = $this->extractCountryCode($data, $serviceName);

                        if ($countryCode) {
                            Log::info("GeoLocation: IP {$ip} detected as {$countryCode} via {$serviceName}");
                            return $countryCode;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("GeoLocation service {$serviceName} failed for IP {$ip}: " . $e->getMessage());
                    continue;
                }
            }

        } catch (\Exception $e) {
            Log::error("GeoLocation: Failed to detect country for IP {$ip}: " . $e->getMessage());
        }

        return null;
    }

    private function extractCountryCode(array $data, string $service): ?string
    {
        switch ($service) {
            case 'ipapi':
                return $data['countryCode'] ?? null;

            case 'ipgeolocation':
                return $data['country_code2'] ?? null;

            case 'ipinfo':
                return $data['country'] ?? null;

            default:
                return null;
        }
    }

    private function isLocalOrPrivateIp(string $ip): bool
    {
        // Check for localhost
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return true;
        }

        // Check for private IP ranges
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    public function getUserCountry($request, $user = null): string
    {
        // 1. Check user's stored country preference
        if ($user && $user->country_code) {
            return $user->country_code;
        }

        // 2. Check session for previously detected country
        if ($request->session()->has('detected_country')) {
            return $request->session()->get('detected_country');
        }

        // 3. Try to detect from IP
        $detectedCountry = $this->getCountryFromRequest($request);

        if ($detectedCountry) {
            // Store in session for future requests
            $request->session()->put('detected_country', $detectedCountry);

            // Update user record if logged in
            if ($user && !$user->country_code) {
                $user->update(['country_code' => $detectedCountry]);
            }

            return $detectedCountry;
        }

        // 4. Default to US if detection fails
        return 'US';
    }

    public function getPaymentProvider($request, $user = null): string
    {
        $country = $this->getUserCountry($request, $user);
        return $country === 'NG' ? 'paystack' : 'lemonsqueezy';
    }
}