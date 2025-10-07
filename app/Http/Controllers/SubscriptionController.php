<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Services\GeoLocationService;
use App\Services\PaystackService;
use App\Services\LemonSqueezyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function __construct(
        private GeoLocationService $geoLocationService,
        private PaystackService $paystackService,
        private LemonSqueezyService $lemonSqueezyService
    ) {}

    public function pricing(Request $request)
    {
        $user = Auth::user();
        $plans = SubscriptionPlan::active()->ordered()->get();

        // Detect user's country and preferred payment provider
        $userCountry = $this->geoLocationService->getUserCountry($request, $user);
        $paymentProvider = $this->geoLocationService->getPaymentProvider($request, $user);

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
                $result = $this->lemonSqueezyService->initializePayment($user, $plan);
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

    public function lemonSqueezySuccess(Request $request)
    {
        // LemonSqueezy will redirect here after successful payment
        // The actual processing happens via webhook, so we just show success message
        return redirect()->route('home')->with('success', 'Payment completed! Your subscription will be activated shortly.');
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
            } else {
                // For LemonSqueezy, cancel via API if it's a recurring subscription
                $success = $this->lemonSqueezyService->cancelSubscription($subscription);
            }

            if ($success) {
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

    public function lemonSqueezyWebhook(Request $request)
    {
        // LemonSqueezy Laravel package handles signature verification automatically
        // if webhook signing is configured in the package

        $data = $request->json()->all();

        try {
            $success = $this->lemonSqueezyService->handleWebhook($data);
            return response('OK', $success ? 200 : 500);

        } catch (\Exception $e) {
            Log::error('LemonSqueezy webhook processing failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return response('Error', 500);
        }
    }
}
