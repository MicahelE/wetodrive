<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Log;
use LemonSqueezy\Laravel\LemonSqueezy;

class LemonSqueezyService
{
    public function initializePayment(User $user, SubscriptionPlan $plan): array
    {
        try {
            // Create pending transaction record
            $reference = 'wetodrive_ls_' . time() . '_' . $user->id;

            $transaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'provider' => 'lemonsqueezy',
                'provider_reference' => $reference,
                'type' => 'subscription',
                'status' => 'pending',
                'amount' => $plan->price_usd,
                'currency' => 'USD',
            ]);

            // Get variant ID for the plan
            $variantId = $this->getVariantIdForPlan($plan);
            
            if (!$variantId) {
                throw new \Exception('No variant ID configured for plan: ' . $plan->slug);
            }

            // Create checkout using LemonSqueezy Checkout class
            $checkout = \LemonSqueezy\Laravel\Checkout::make(
                config('lemon-squeezy.store_id'),
                $variantId
            )
            ->withName($user->name)
            ->withEmail($user->email)
            ->redirectTo(route('lemonsqueezy.success'))
            ->withCustomData([
                'user_id' => (string) $user->id,
                'plan_id' => (string) $plan->id,
                'transaction_id' => (string) $transaction->id,
                'reference' => $reference,
            ]);

            // Get checkout URL
            $checkoutUrl = $checkout->url();

            // Store checkout reference in session for later retrieval
            session([
                'lemon_squeezy_checkout' => [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'transaction_id' => $transaction->id,
                    'reference' => $reference,
                ]
            ]);

            Log::info('LemonSqueezy checkout created', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'reference' => $reference,
                'checkout_url' => $checkoutUrl
            ]);

            return [
                'success' => true,
                'checkout_url' => $checkoutUrl,
                'reference' => $reference,
            ];

        } catch (\Exception $e) {
            Log::error('LemonSqueezy checkout creation failed', [
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

    private function getVariantIdForPlan(SubscriptionPlan $plan): string
    {
        // This should be configured in your environment or database
        // You need to create products/variants in LemonSqueezy dashboard first
        $variants = [
            'pro' => config('lemon-squeezy.variant_ids.pro'),
            'premium' => config('lemon-squeezy.variant_ids.premium'),
        ];

        return $variants[$plan->slug] ?? '';
    }

    public function verifyOrder(string $orderId): array
    {
        try {
            $response = LemonSqueezy::api('get', "orders/{$orderId}");
            
            if (isset($response['data'])) {
                return [
                    'success' => true,
                    'data' => $response['data']
                ];
            }

            return [
                'success' => false,
                'message' => 'Order not found'
            ];

        } catch (\Exception $e) {
            Log::error('LemonSqueezy order verification failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function handleSuccessfulPayment(array $orderData): bool
    {
        try {
            // Try multiple locations for custom data
            $customData = 
                $orderData['attributes']['first_order_item']['product_options']['custom'] ?? 
                $orderData['attributes']['checkout_data']['custom'] ?? 
                $orderData['attributes']['custom'] ?? 
                [];

            $userId = $customData['user_id'] ?? null;
            $planId = $customData['plan_id'] ?? null;
            $transactionId = $customData['transaction_id'] ?? null;

            if (!$userId || !$planId || !$transactionId) {
                Log::error('LemonSqueezy: Invalid order data', [
                    'order_id' => $orderData['id'],
                    'custom_data' => $customData,
                    'order_attributes' => $orderData['attributes'] ?? null
                ]);
                return false;
            }

            $user = User::find($userId);
            $plan = SubscriptionPlan::find($planId);
            $transaction = PaymentTransaction::find($transactionId);

            if (!$user || !$plan || !$transaction) {
                Log::error('LemonSqueezy: Referenced entities not found', [
                    'user_id' => $userId,
                    'plan_id' => $planId,
                    'transaction_id' => $transactionId
                ]);
                return false;
            }

            // Check if subscription already exists for this order
            $existingSubscription = UserSubscription::where('provider_subscription_id', $orderData['id'])
                ->where('payment_provider', 'lemonsqueezy')
                ->first();

            if ($existingSubscription) {
                Log::info('LemonSqueezy: Subscription already exists for this order', [
                    'order_id' => $orderData['id'],
                    'subscription_id' => $existingSubscription->id
                ]);
                return true;
            }

            // Mark transaction as successful
            $transaction->markAsSuccessful($orderData);

            // Cancel any existing active subscriptions
            $this->cancelExistingSubscriptions($user);

            // Create new subscription
            $subscription = UserSubscription::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'payment_provider' => 'lemonsqueezy',
                'provider_subscription_id' => $orderData['id'],
                'status' => 'active',
                'started_at' => now(),
                'expires_at' => now()->addMonth(),
                'transfers_used' => 0,
                'period_resets_at' => now()->addMonth(),
                'amount_paid' => $orderData['attributes']['total'] / 100, // Convert from cents
                'currency' => $orderData['attributes']['currency'],
                'metadata' => $orderData,
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

            Log::info('LemonSqueezy subscription created successfully', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'plan' => $plan->name,
                'order_id' => $orderData['id']
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('LemonSqueezy: Failed to handle successful payment', [
                'error' => $e->getMessage(),
                'order_data' => $orderData
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

    public function createProduct(SubscriptionPlan $plan): array
    {
        try {
            // Create product in LemonSqueezy
            $product = LemonSqueezy::api('post', 'products', [
                'data' => [
                    'type' => 'products',
                    'attributes' => [
                        'name' => "WetoDrive {$plan->name}",
                        'description' => "WetoDrive {$plan->name} Plan - " . implode(', ', $plan->features ?? []),
                        'slug' => "wetodrive-{$plan->slug}",
                        'status' => 'published',
                    ],
                    'relationships' => [
                        'store' => [
                            'data' => [
                                'type' => 'stores',
                                'id' => (string) config('lemon-squeezy.store_id')
                            ]
                        ]
                    ]
                ]
            ]);

            if ($product->json('data.id') !== null) {
                // Create variant (pricing option)
                $variant = LemonSqueezy::api('post', 'variants', [
                    'data' => [
                        'type' => 'variants',
                        'attributes' => [
                            'name' => "Monthly",
                            'price' => $plan->price_usd * 100, // Convert to cents
                            'is_subscription' => false, // One-time payment for now
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                    'id' => (string) $product->json('data.id')
                                ]
                            ]
                        ]
                    ]
                ]);

                Log::info('LemonSqueezy product and variant created', [
                    'plan_id' => $plan->id,
                    'product_id' => $product['data']['id'],
                    'variant_id' => $variant['data']['id'] ?? null
                ]);

                return [
                    'success' => true,
                    'product_id' => $product->json('data.id'),
                    'variant_id' => $variant->json('data.id'),
                    'data' => $product->json('data')
                ];
            }

            throw new \Exception('Failed to create LemonSqueezy product');

        } catch (\Exception $e) {
            Log::error('LemonSqueezy product creation failed', [
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
            $eventName = $data['meta']['event_name'] ?? null;

            switch ($eventName) {
                case 'order_created':
                    return $this->handleOrderCreated($data['data']);

                case 'subscription_created':
                    return $this->handleSubscriptionCreated($data['data']);

                case 'subscription_updated':
                    return $this->handleSubscriptionUpdated($data['data']);

                case 'subscription_cancelled':
                    return $this->handleSubscriptionCancelled($data['data']);

                case 'subscription_expired':
                    return $this->handleSubscriptionExpired($data['data']);

                default:
                    Log::info('LemonSqueezy webhook event not handled', ['event' => $eventName]);
                    return true;
            }

        } catch (\Exception $e) {
            Log::error('LemonSqueezy webhook handling failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return false;
        }
    }

    private function handleOrderCreated(array $data): bool
    {
        if ($data['attributes']['status'] === 'paid') {
            return $this->handleSuccessfulPayment($data);
        }

        Log::info('LemonSqueezy order created but not paid', [
            'order_id' => $data['id'],
            'status' => $data['attributes']['status']
        ]);

        return true;
    }

    private function handleSubscriptionCreated(array $data): bool
    {
        Log::info('LemonSqueezy subscription created', ['data' => $data]);
        return true;
    }

    private function handleSubscriptionUpdated(array $data): bool
    {
        Log::info('LemonSqueezy subscription updated', ['data' => $data]);
        return true;
    }

    private function handleSubscriptionCancelled(array $data): bool
    {
        // Handle subscription cancellation
        $subscriptionId = $data['id'];

        $subscription = UserSubscription::where('provider_subscription_id', $subscriptionId)
            ->where('payment_provider', 'lemonsqueezy')
            ->first();

        if ($subscription) {
            $subscription->cancel();

            Log::info('LemonSqueezy subscription cancelled', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id
            ]);
        }

        return true;
    }

    private function handleSubscriptionExpired(array $data): bool
    {
        // Handle subscription expiration
        $subscriptionId = $data['id'];

        $subscription = UserSubscription::where('provider_subscription_id', $subscriptionId)
            ->where('payment_provider', 'lemonsqueezy')
            ->first();

        if ($subscription) {
            $subscription->update([
                'status' => 'expired'
            ]);

            Log::info('LemonSqueezy subscription expired', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id
            ]);
        }

        return true;
    }

    public function cancelSubscription(UserSubscription $subscription): bool
    {
        try {
            if ($subscription->payment_provider !== 'lemonsqueezy') {
                return false;
            }

            // Cancel subscription via LemonSqueezy API
            $response = LemonSqueezy::api('patch', "subscriptions/{$subscription->provider_subscription_id}", [
                'data' => [
                    'type' => 'subscriptions',
                    'id' => (string) $subscription->provider_subscription_id,
                    'attributes' => [
                        'cancelled' => true
                    ]
                ]
            ]);

            if ($response->json('data') !== null) {
                $subscription->cancel();

                Log::info('LemonSqueezy subscription cancelled via API', [
                    'subscription_id' => $subscription->id,
                    'provider_subscription_id' => $subscription->provider_subscription_id
                ]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Failed to cancel LemonSqueezy subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}