<?php

namespace App\Http\Controllers;

use App\Mail\SubscriptionActivatedMail;
use App\Mail\SubscriptionCancelledMail;
use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use App\Services\GeoLocationService;
use App\Services\PaystackService;
use App\Services\PolarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SubscriptionController extends Controller
{
    public function __construct(
        private GeoLocationService $geoLocationService,
        private PaystackService $paystackService,
        private PolarService $polarService
    ) {}

    public function pricing(Request $request)
    {
        $user = Auth::user();
        $plans = SubscriptionPlan::active()->ordered()->get();

        // Detect user's country and preferred payment provider
        $userCountry = $this->geoLocationService->getUserCountry($request, $user);
        $paymentProvider = $this->geoLocationService->getPaymentProvider($request, $user);

        // Log location detection for debugging
        Log::info('Location Detection Debug', [
            'user_id' => $user?->id,
            'user_country' => $userCountry,
            'payment_provider' => $paymentProvider,
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'country_name' => $userCountry === 'NG' ? 'Nigeria' : 'International'
        ]);

        // Update user's country if not set
        if ($user && !$user->country_code) {
            $user->update(['country_code' => $userCountry]);
        }

        return view('subscription.pricing', compact('plans', 'userCountry', 'paymentProvider'));
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id'
        ]);

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('auth.google')->with('error', 'Please login to subscribe.');
        }

        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        // Don't allow subscribing to free plan
        if ($plan->slug === 'free') {
            return redirect()->back()->with('error', 'You are already on the free plan.');
        }

        // Determine payment provider based on user's location
        $paymentProvider = $user->getPreferredPaymentProvider();

        try {
            if ($paymentProvider === 'paystack') {
                $result = $this->paystackService->initializePayment($user, $plan);
            } else {
                $result = $this->polarService->initializePayment($user, $plan);
            }

            if ($result['success']) {
                Log::info('payment.redirect_issued', [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'provider' => $paymentProvider,
                    'reference' => $result['reference'] ?? null,
                ]);

                if ($paymentProvider === 'paystack') {
                    return redirect($result['authorization_url']);
                } else {
                    return redirect($result['checkout_url']);
                }
            } else {
                return redirect()->back()->with('error', 'Failed to initialize payment: ' . $result['message']);
            }

        } catch (\Exception $e) {
            Log::error('Subscription initialization failed', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'provider' => $paymentProvider,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    public function paystackCallback(Request $request)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return redirect()->route('home')->with('error', 'Invalid payment reference.');
        }

        PaymentTransaction::where('provider', 'paystack')
            ->where('provider_reference', $reference)
            ->update(['returned_at' => now()]);

        Log::info('payment.user_returned', [
            'provider' => 'paystack',
            'reference' => $reference,
        ]);

        try {
            $verification = $this->paystackService->verifyPayment($reference);

            if ($verification['success']) {
                $success = $this->paystackService->handleSuccessfulPayment($verification);

                if ($success) {
                    return redirect()->route('home')->with('success', 'Subscription activated successfully! You can now enjoy your new plan.');
                } else {
                    return redirect()->route('home')->with('error', 'Payment verified but subscription setup failed. Please contact support.');
                }
            } else {
                return redirect()->route('home')->with('error', 'Payment verification failed: ' . $verification['message']);
            }

        } catch (\Exception $e) {
            Log::error('Paystack callback error', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('home')->with('error', 'Payment processing failed. Please contact support.');
        }
    }

    public function polarSuccess(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('home')->with('error', 'Please login to view your subscription.');
        }

        // The Polar return carries ?checkout_id=, not our reference, so stamp the
        // most-recent pending polar transaction we redirected this user to.
        $transaction = PaymentTransaction::where('user_id', $user->id)
            ->where('provider', 'polar')
            ->where('status', 'pending')
            ->whereNotNull('redirected_at')
            ->latest('redirected_at')
            ->first();

        if ($transaction) {
            $transaction->update(['returned_at' => now()]);
        }

        Log::info('payment.user_returned', [
            'provider' => 'polar',
            'user_id' => $user->id,
            'checkout_id' => $request->query('checkout_id'),
            'transaction_id' => $transaction?->id,
        ]);

        try {
            $this->polarService->syncSubscriptionsFromPolar($user);
        } catch (\Throwable $e) {
            Log::warning('Polar success callback sync failed; webhook will reconcile', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('home')->with('info', 'Payment received! Your subscription will be activated momentarily. Please refresh in a few seconds.');
        }

        return redirect()->route('home')->with('success', 'Subscription activated successfully! You can now enjoy your new plan.');
    }

    public function manage(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('auth.google');
        }

        $activeSubscription = $user->activeSubscription;
        $subscriptionHistory = $user->subscriptions()->with('subscriptionPlan')->latest()->get();
        $paymentHistory = $user->paymentTransactions()->with('userSubscription.subscriptionPlan')->latest()->get();

        return view('subscription.manage', compact('activeSubscription', 'subscriptionHistory', 'paymentHistory'));
    }

    public function cancel(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->hasActiveSubscription()) {
            return redirect()->back()->with('error', 'No active subscription to cancel.');
        }

        try {
            $subscription = $user->activeSubscription;

            // Cancelling only stops the renewal. The user keeps the tier they paid
            // for until expires_at, at which point subscriptions:expire retires the
            // row and downgrades them — so no immediate downgrade here.
            if ($subscription->payment_provider === 'polar') {
                $success = $this->polarService->cancelSubscription($subscription);
            } else {
                // Paystack (one-time charge) and legacy LemonSqueezy rows: local only.
                $subscription->cancel();
                $success = true;
            }

            if ($success) {
                try {
                    Mail::to($user)->send(new SubscriptionCancelledMail(
                        $user,
                        $subscription->subscriptionPlan->name,
                        $subscription->expires_at,
                    ));
                } catch (\Exception $mailEx) {
                    Log::warning('Failed to send subscription cancelled email', ['error' => $mailEx->getMessage()]);
                }

                $accessUntil = $subscription->expires_at?->format('M j, Y');

                return redirect()->back()->with('success', $accessUntil
                    ? "Subscription cancelled. You keep {$subscription->subscriptionPlan->name} access until {$accessUntil}."
                    : 'Subscription cancelled successfully.');
            } else {
                return redirect()->back()->with('error', 'Failed to cancel subscription. Please contact support.');
            }

        } catch (\Exception $e) {
            Log::error('Subscription cancellation failed', [
                'user_id' => $user->id,
                'subscription_id' => $user->active_subscription_id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    public function upgrade(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id'
        ]);

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('auth.google');
        }

        $newPlan = SubscriptionPlan::findOrFail($request->plan_id);
        $currentSubscription = $user->activeSubscription;

        // Check if it's actually an upgrade
        if ($currentSubscription && $currentSubscription->subscriptionPlan->sort_order >= $newPlan->sort_order) {
            return redirect()->back()->with('error', 'You can only upgrade to a higher plan.');
        }

        // Redirect to subscription flow
        return $this->subscribe($request);
    }

    public function paystackWebhook(Request $request)
    {
        // Verify webhook signature
        $signature = $request->header('x-paystack-signature');
        $body = $request->getContent();
        $expectedSignature = hash_hmac('sha512', $body, config('services.paystack.webhook_secret'));

        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('Paystack webhook signature verification failed');
            return response('Unauthorized', 401);
        }

        $data = $request->json()->all();

        try {
            $success = $this->paystackService->handleWebhook($data);
            return response('OK', $success ? 200 : 500);

        } catch (\Exception $e) {
            Log::error('Paystack webhook processing failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return response('Error', 500);
        }
    }

    public function polarWebhook(Request $request)
    {
        $secret = config('polar.webhook_secret');
        $body = $request->getContent();

        if ($secret) {
            $id = $request->header('webhook-id');
            $timestamp = $request->header('webhook-timestamp');
            $signatureHeader = $request->header('webhook-signature');

            if (!$id || !$timestamp || !$signatureHeader) {
                Log::warning('Polar webhook missing Standard Webhooks headers');
                return response('Unauthorized', 401);
            }

            $signedPayload = $id . '.' . $timestamp . '.' . $body;

            // Polar follows Standard Webhooks but its secret carries a "polar_"
            // prefix rather than the spec's "whsec_", and it's unclear whether the
            // key is used raw or base64-decoded. Accept the signature under any
            // reasonable derivation so verification doesn't depend on guessing.
            $valid = false;
            foreach ($this->polarSecretKeyCandidates($secret) as $key) {
                $expected = base64_encode(hash_hmac('sha256', $signedPayload, $key, true));
                foreach (explode(' ', $signatureHeader) as $pair) {
                    [, $sig] = array_pad(explode(',', $pair, 2), 2, null);
                    if ($sig !== null && hash_equals($expected, $sig)) {
                        $valid = true;
                        break 2;
                    }
                }
            }

            if (!$valid) {
                Log::warning('Polar webhook signature verification failed');
                return response('Unauthorized', 401);
            }
        }

        $data = json_decode($body, true) ?: [];

        try {
            $success = $this->polarService->handleWebhook($data);
            return response('OK', $success ? 200 : 500);
        } catch (\Exception $e) {
            Log::error('Polar webhook processing failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return response('Error', 500);
        }
    }

    /**
     * Possible HMAC keys for a Polar webhook secret. Polar uses the Standard
     * Webhooks scheme but with a "polar_" prefix, and may use the key raw or
     * base64-decoded — so we try each plausible derivation.
     *
     * @return list<string>
     */
    private function polarSecretKeyCandidates(string $secret): array
    {
        $candidates = [$secret]; // raw, as displayed

        foreach (['whsec_', 'polar_'] as $prefix) {
            if (str_starts_with($secret, $prefix)) {
                $stripped = substr($secret, strlen($prefix));
                $candidates[] = $stripped; // raw without prefix
                $decoded = base64_decode($stripped, true);
                if ($decoded !== false) {
                    $candidates[] = $decoded; // base64-decoded (Standard Webhooks)
                }
            }
        }

        $decodedWhole = base64_decode($secret, true);
        if ($decodedWhole !== false) {
            $candidates[] = $decodedWhole;
        }

        return array_values(array_unique($candidates));
    }
}
