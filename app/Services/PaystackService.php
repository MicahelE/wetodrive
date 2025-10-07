<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Log;
use Yabacon\Paystack\Paystack;

class PaystackService
{
    private $paystack;

    public function __construct()
    {
        $this->paystack = new Paystack(config('services.paystack.secret_key'));
    }

    public function initializePayment(User $user, SubscriptionPlan $plan): array
    {
        try {
            $amount = $plan->price_ngn * 100; // Convert to kobo
            $reference = 'wetodrive_' . time() . '_' . $user->id;

            // Create pending transaction record
            $transaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'provider' => 'paystack',
                'provider_reference' => $reference,
                'type' => 'subscription',
                'status' => 'pending',
                'amount' => $plan->price_ngn,
                'currency' => 'NGN',
            ]);

            $response = $this->paystack->transaction->initialize([
                'amount' => $amount,
                'email' => $user->email,
                'reference' => $reference,
                'currency' => 'NGN',
                'callback_url' => route('paystack.callback'),
                'metadata' => [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'transaction_id' => $transaction->id,
                    'plan_name' => $plan->name,
                ]
            ]);

            if ($response->status) {
                Log::info('Paystack payment initialized', [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'reference' => $reference,
                    'amount' => $amount
                ]);

                return [
                    'success' => true,
                    'data' => $response->data,
                    'authorization_url' => $response->data->authorization_url,
                    'access_code' => $response->data->access_code,
                    'reference' => $reference,
                ];
            }

            throw new \Exception('Failed to initialize Paystack payment: ' . $response->message);

        } catch (\Exception $e) {
            Log::error('Paystack payment initialization failed', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function verifyPayment(string $reference): array
    {
        try {
            $response = $this->paystack->transaction->verify([
                'reference' => $reference
            ]);

            if ($response->status && $response->data->status === 'success') {
                $data = $response->data;

                Log::info('Paystack payment verified successfully', [
                    'reference' => $reference,
                    'amount' => $data->amount,
                    'customer_email' => $data->customer->email
                ]);

                return [
                    'success' => true,
                    'data' => $data,
                    'amount' => $data->amount / 100, // Convert from kobo
                    'currency' => $data->currency,
                    'customer_email' => $data->customer->email,
                    'metadata' => $data->metadata ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => 'Payment verification failed: ' . ($response->message ?? 'Unknown error')
            ];

        } catch (\Exception $e) {
            Log::error('Paystack payment verification failed', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function handleSuccessfulPayment(array $paymentData): bool
    {
        try {
            $metadata = $paymentData['metadata'];
            $user = User::find($metadata->user_id);
            $plan = SubscriptionPlan::find($metadata->plan_id);
            $transaction = PaymentTransaction::find($metadata->transaction_id);

            if (!$user || !$plan || !$transaction) {
                Log::error('Paystack: Invalid payment data', [
                    'user_id' => $metadata->user_id ?? null,
                    'plan_id' => $metadata->plan_id ?? null,
                    'transaction_id' => $metadata->transaction_id ?? null
                ]);
                return false;
            }

            // Mark transaction as successful
            $transaction->markAsSuccessful($paymentData);

            // Cancel any existing active subscriptions
            $this->cancelExistingSubscriptions($user);

            // Create new subscription
            $subscription = UserSubscription::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'payment_provider' => 'paystack',
                'provider_subscription_id' => $paymentData['data']->reference ?? null,
                'status' => 'active',
                'started_at' => now(),
                'expires_at' => now()->addMonth(),
                'transfers_used' => 0,
                'period_resets_at' => now()->addMonth(),
                'amount_paid' => $paymentData['amount'],
                'currency' => $paymentData['currency'],
                'metadata' => $paymentData,
            ]);

            // Update user's subscription info
            $user->update([
                'subscription_tier' => $plan->slug,
                'active_subscription_id' => $subscription->id,
            ]);

            // Link transaction to subscription
            $transaction->update([
                'user_subscription_id' => $subscription->id
            ]);

            Log::info('Paystack subscription created successfully', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'plan' => $plan->name
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Paystack: Failed to handle successful payment', [
                'error' => $e->getMessage(),
                'payment_data' => $paymentData
            ]);

            return false;
        }
    }

    private function cancelExistingSubscriptions(User $user): void
    {
        $user->subscriptions()
            ->where('status', 'active')
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now()
            ]);

        // Clear active subscription reference
        $user->update(['active_subscription_id' => null]);
    }

    public function createSubscriptionPlan(SubscriptionPlan $plan): array
    {
        try {
            // Paystack doesn't require separate plan creation for one-time payments
            // But we can create plans for recurring subscriptions if needed in the future

            $response = $this->paystack->plan->create([
                'name' => "WetoDrive {$plan->name}",
                'amount' => $plan->price_ngn * 100, // Convert to kobo
                'interval' => 'monthly',
                'currency' => 'NGN',
                'description' => "WetoDrive {$plan->name} Plan",
            ]);

            if ($response->status) {
                Log::info('Paystack plan created', [
                    'plan_id' => $plan->id,
                    'paystack_plan_code' => $response->data->plan_code
                ]);

                return [
                    'success' => true,
                    'plan_code' => $response->data->plan_code,
                    'data' => $response->data
                ];
            }

            throw new \Exception('Failed to create Paystack plan: ' . $response->message);

        } catch (\Exception $e) {
            Log::error('Paystack plan creation failed', [
                'plan_id' => $plan->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function handleWebhook(array $data): bool
    {
        try {
            $event = $data['event'] ?? null;

            switch ($event) {
                case 'charge.success':
                    return $this->handleChargeSuccess($data['data']);

                case 'subscription.create':
                case 'subscription.enable':
                    return $this->handleSubscriptionActivated($data['data']);

                case 'subscription.disable':
                case 'subscription.not_renew':
                    return $this->handleSubscriptionCancelled($data['data']);

                default:
                    Log::info('Paystack webhook event not handled', ['event' => $event]);
                    return true;
            }

        } catch (\Exception $e) {
            Log::error('Paystack webhook handling failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return false;
        }
    }

    private function handleChargeSuccess(array $data): bool
    {
        $reference = $data['reference'] ?? null;

        if (!$reference) {
            Log::warning('Paystack webhook: No reference in charge success data');
            return false;
        }

        $verification = $this->verifyPayment($reference);

        if ($verification['success']) {
            return $this->handleSuccessfulPayment($verification);
        }

        return false;
    }

    private function handleSubscriptionActivated(array $data): bool
    {
        // Handle subscription activation events
        Log::info('Paystack subscription activated', ['data' => $data]);
        return true;
    }

    private function handleSubscriptionCancelled(array $data): bool
    {
        // Handle subscription cancellation events
        Log::info('Paystack subscription cancelled', ['data' => $data]);
        return true;
    }
}