<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Remember where a visitor arrived from, for as long as it takes them to sign up.
 *
 * The referrer only exists on the landing request: by the time they reach
 * /auth/google the referrer is our own site, and after the Google round trip it
 * is accounts.google.com. So it has to be caught here and parked in the session.
 */
class CaptureSignupSource
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldCapture($request)) {
            $request->session()->put('signup_source', [
                'referrer' => substr((string) $request->headers->get('referer'), 0, 512) ?: null,
                'landing' => substr($request->getRequestUri(), 0, 512),
            ]);
        }

        return $next($request);
    }

    private function shouldCapture(Request $request): bool
    {
        // First touch only. A later page view would overwrite the real source
        // with our own domain, which is the mistake this exists to avoid.
        return $request->isMethod('GET')
            && ! $request->user()
            && ! $request->session()->has('signup_source')
            && ! $request->ajax();
    }
}
