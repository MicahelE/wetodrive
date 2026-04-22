<?php

namespace App\Http\Controllers;

use App\Mail\SubscriptionActivatedMail;
use App\Mail\SubscriptionCancelledMail;
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

            if ($subscription->payment_provider === 'paystack') {
                // For Paystack, we just cancel locally since it's one-time payment
                $subscription->cancel();
                $user->update(['active_subscription_id' => null, 'subscription_tier' => 'free']);
                $success = true;
            } elseif ($subscription->payment_provider === 'polar') {
                $success = $this->polarService->cancelSubscription($subscription);
            } else {
                // Legacy LemonSqueezy rows: cancel locally only (LS integration removed)
                $subscription->cancel();
                $user->update(['active_subscription_id' => null, 'subscription_tier' => 'free']);
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

                return redirect()->back()->with('success', 'Subscription cancelled successfully.');
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

            $secretBytes = str_starts_with($secret, 'whsec_')
                ? base64_decode(substr($secret, 6))
                : $secret;

            $signedPayload = $id . '.' . $timestamp . '.' . $body;
            $expected = base64_encode(hash_hmac('sha256', $signedPayload, $secretBytes, true));

            $valid = false;
            foreach (explode(' ', $signatureHeader) as $pair) {
                [, $sig] = array_pad(explode(',', $pair, 2), 2, null);
                if ($sig !== null && hash_equals($expected, $sig)) {
                    $valid = true;
                    break;
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
}
