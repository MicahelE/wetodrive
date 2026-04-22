<?php

namespace App\Services;

use App\Mail\SubscriptionActivatedMail;
use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Polar\Models\Components\CheckoutCreate;
use Polar\Models\Components\CustomerSessionCustomerExternalIDCreate;
use Polar\Models\Errors\APIException;
use Polar\Models\Operations\SubscriptionsListRequest;
use Polar\Polar;

class PolarService
{
    protected ?Polar $client = null;

    protected function client(): Polar
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $apiKey = config('polar.api_key');

        if (!$apiKey) {
            throw new \RuntimeException('POLAR_API_KEY is not configured.');
        }

        $builder = Polar::builder()->setSecurity($apiKey);

        if (config('polar.environment') === 'sandbox') {
            $builder->setServer(Polar::SERVER_SANDBOX);
        }

        return $this->client = $builder->build();
    }

    public function initializePayment(User $user, SubscriptionPlan $plan): array
    {
        try {
            $reference = 'wetodrive_polar_' . time() . '_' . $user->id;

            $transaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'provider' => 'polar',
                'provider_reference' => $reference,
                'type' => 'subscription',
                'status' => 'pending',
                'amount' => $plan->price_usd,
                'currency' => 'USD',
            ]);

            $productId = $this->getProductIdForPlan($plan);

            if (!$productId) {
                throw new \RuntimeException('No Polar product ID configured for plan: ' . $plan->slug);
            }

            $checkout = new CheckoutCreate(
                products: [$productId],
                metadata: [
                    'user_id' => (string) $user->id,
                    'plan_id' => (string) $plan->id,
                    'transaction_id' => (string) $transaction->id,
                    'reference' => $reference,
                ],
                externalCustomerId: (string) $user->id,
                customerName: $user->name,
                customerEmail: $user->email,
                successUrl: route('polar.success') . '?checkout_id={CHECKOUT_ID}',
            );

            $response = $this->client()->checkouts->create($checkout);
            $checkoutUrl = $response->checkout->url;

            Log::info('Polar checkout created', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'reference' => $reference,
                'checkout_url' => $checkoutUrl,
            ]);

            return [
                'success' => true,
                'checkout_url' => $checkoutUrl,
                'reference' => $reference,
            ];
        } catch (\Throwable $e) {
            Log::error('Polar checkout creation failed', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function getProductIdForPlan(SubscriptionPlan $plan): ?string
    {
        return config("polar.product_ids.{$plan->slug}");
    }

    public function handleWebhook(array $data): bool
    {
        try {
            $eventName = $data['type'] ?? null;
            $eventData = $data['data'] ?? [];

            switch ($eventName) {
                case 'subscription.created':
                    return $this->handleSubscriptionCreated($eventData);

                case 'subscription.active':
                case 'subscription.updated':
                    return $this->handleSubscriptionUpdated($eventData);

                case 'subscription.canceled':
                case 'subscription.cancelled':
                    return $this->handleSubscriptionCancelled($eventData);

                case 'subscription.revoked':
                    return $this->handleSubscriptionRevoked($eventData);

                case 'order.created':
                case 'order.paid':
                    return $this->handleOrderPaid($eventData);

                default:
                    Log::info('Polar webhook event not handled', ['event' => $eventName]);
                    return true;
            }
        } catch (\Throwable $e) {
            Log::error('Polar webhook handling failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            return false;
        }
    }

    private function handleSubscriptionCreated(array $data): bool
    {
        $subscription = $this->findOrCreateSubscription($data);

        if (!$subscription) {
            Log::warning('Polar subscription.created: could not locate or create subscription', [
                'polar_id' => $data['id'] ?? null,
            ]);
            return true;
        }

        return true;
    }

    private function handleSubscriptionUpdated(array $data): bool
    {
        $status = $data['status'] ?? null;
        $subscription = $this->findSubscription($data);

        if (!$subscription) {
            $subscription = $this->findOrCreateSubscription($data);
        }

        if (!$subscription) {
            Log::warning('Polar subscription.updated: subscription not found and could not be auto-created', [
                'polar_id' => $data['id'] ?? null,
            ]);
            return true;
        }

        if ($status === 'active') {
            $renewsAt = $data['current_period_end'] ?? null;
            $previousPeriodEnd = $subscription->expires_at?->toIso8601String();

            $subscription->update([
                'expires_at' => $renewsAt,
                'period_resets_at' => $renewsAt,
                'metadata' => $data,
            ]);

            if ($previousPeriodEnd && $renewsAt && $renewsAt !== $previousPeriodEnd) {
                $this->recordRenewal($subscription);
            }
        }

        return true;
    }

    private function handleSubscriptionCancelled(array $data): bool
    {
        $subscription = $this->findSubscription($data);

        if ($subscription) {
            $subscription->update(['metadata' => $data]);

            Log::info('Polar subscription marked as canceling at period end', [
                'subscription_id' => $subscription->id,
                'polar_id' => $data['id'] ?? null,
                'cancel_at_period_end' => $data['cancel_at_period_end'] ?? null,
            ]);
        }

        return true;
    }

    private function handleSubscriptionRevoked(array $data): bool
    {
        $subscription = $this->findSubscription($data);

        if ($subscription) {
            $subscription->cancel();
            $subscription->user?->update([
                'active_subscription_id' => null,
                'subscription_tier' => 'free',
            ]);

            Log::info('Polar subscription revoked', [
                'subscription_id' => $subscription->id,
                'polar_id' => $data['id'] ?? null,
            ]);
        }

        return true;
    }

    private function handleOrderPaid(array $data): bool
    {
        $metadata = $data['metadata'] ?? [];
        $transactionId = $metadata['transaction_id'] ?? null;

        if (!$transactionId) {
            Log::info('Polar order event missing transaction_id metadata', [
                'polar_order_id' => $data['id'] ?? null,
            ]);
            return true;
        }

        $transaction = PaymentTransaction::find($transactionId);

        if ($transaction && $transaction->status === 'pending') {
            $transaction->update([
                'status' => 'success',
                'paid_at' => now(),
                'provider_response' => $data,
            ]);

            Log::info('Polar order marked as paid', [
                'transaction_id' => $transaction->id,
                'polar_order_id' => $data['id'] ?? null,
            ]);
        }

        return true;
    }

    private function findSubscription(array $data): ?UserSubscription
    {
        $polarId = $data['id'] ?? null;

        if (!$polarId) {
            return null;
        }

        return UserSubscription::where('provider_subscription_id', $polarId)
            ->where('payment_provider', 'polar')
            ->first();
    }

    private function findOrCreateSubscription(array $data): ?UserSubscription
    {
        $existing = $this->findSubscription($data);
        if ($existing) {
            return $existing;
        }

        $metadata = $data['metadata'] ?? [];
        $userId = $metadata['user_id'] ?? ($data['customer']['external_id'] ?? null);
        $planId = $metadata['plan_id'] ?? null;

        if (!$userId) {
            return null;
        }

        $user = User::find($userId);
        if (!$user) {
            return null;
        }

        $plan = $planId ? SubscriptionPlan::find($planId) : $this->resolvePlanFromProduct($data['product_id'] ?? null);

        if (!$plan) {
            Log::warning('Polar auto-sync: plan not resolvable', [
                'user_id' => $userId,
                'plan_id' => $planId,
                'product_id' => $data['product_id'] ?? null,
            ]);
            return null;
        }

        $this->cancelExistingSubscriptions($user);

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'payment_provider' => 'polar',
            'provider_subscription_id' => $data['id'],
            'status' => $this->mapPolarStatus($data['status'] ?? 'active'),
            'started_at' => $data['started_at'] ?? ($data['current_period_start'] ?? now()),
            'expires_at' => $data['current_period_end'] ?? now()->addMonth(),
            'transfers_used' => 0,
            'period_resets_at' => $data['current_period_end'] ?? now()->addMonth(),
            'amount_paid' => $plan->price_usd,
            'currency' => 'USD',
            'metadata' => $data,
        ]);

        $user->update([
            'subscription_tier' => $plan->slug,
            'active_subscription_id' => $subscription->id,
        ]);

        $transactionId = $metadata['transaction_id'] ?? null;
        if ($transactionId && $transaction = PaymentTransaction::find($transactionId)) {
            $transaction->update([
                'status' => 'success',
                'paid_at' => now(),
                'user_subscription_id' => $subscription->id,
                'provider_response' => $data,
            ]);
        }

        Log::info('Polar subscription created', [
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'plan' => $plan->name,
            'polar_id' => $data['id'],
        ]);

        try {
            Mail::to($user)->send(new SubscriptionActivatedMail($user, $subscription, $plan));
        } catch (\Throwable $mailEx) {
            Log::warning('Failed to send subscription activated email', ['error' => $mailEx->getMessage()]);
        }

        return $subscription;
    }

    private function resolvePlanFromProduct(?string $productId): ?SubscriptionPlan
    {
        if (!$productId) {
            return null;
        }

        foreach ((array) config('polar.product_ids') as $slug => $configuredId) {
            if ($configuredId === $productId) {
                return SubscriptionPlan::where('slug', $slug)->first();
            }
        }

        return null;
    }

    private function mapPolarStatus(string $polarStatus): string
    {
        return match ($polarStatus) {
            'active', 'trialing' => 'active',
            'canceled', 'cancelled' => 'cancelled',
            'past_due', 'unpaid' => 'past_due',
            'incomplete', 'incomplete_expired' => 'past_due',
            default => 'expired',
        };
    }

    private function cancelExistingSubscriptions(User $user): void
    {
        $user->subscriptions()
            ->where('status', 'active')
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

        $user->update(['active_subscription_id' => null]);
    }

    private function recordRenewal(UserSubscription $subscription): void
    {
        $subscription->update([
            'transfers_used' => 0,
        ]);

        PaymentTransaction::create([
            'user_id' => $subscription->user_id,
            'user_subscription_id' => $subscription->id,
            'provider' => 'polar',
            'provider_reference' => 'renewal_' . $subscription->provider_subscription_id . '_' . now()->timestamp,
            'type' => 'renewal',
            'status' => 'success',
            'amount' => $subscription->amount_paid,
            'currency' => $subscription->currency,
            'paid_at' => now(),
        ]);

        Log::info('Polar subscription renewed', [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
        ]);
    }

    public function cancelSubscription(UserSubscription $subscription): bool
    {
        if ($subscription->payment_provider !== 'polar') {
            $subscription->cancel();
            return true;
        }

        try {
            $this->client()->subscriptions->revoke($subscription->provider_subscription_id);

            $subscription->cancel();

            Log::info('Polar subscription revoked via API', [
                'subscription_id' => $subscription->id,
                'polar_id' => $subscription->provider_subscription_id,
            ]);

            return true;
        } catch (APIException $e) {
            Log::error('Failed to revoke Polar subscription', [
                'subscription_id' => $subscription->id,
                'status' => $e->statusCode,
                'body' => $e->body,
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('Failed to revoke Polar subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function getCustomerPortalUrl(User $user): ?string
    {
        try {
            $request = new CustomerSessionCustomerExternalIDCreate(
                externalCustomerId: (string) $user->id,
            );

            $response = $this->client()->customerSessions->create($request);

            return $response->customerSession->customerPortalUrl;
        } catch (\Throwable $e) {
            Log::warning('Polar customer portal URL generation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function syncSubscriptionsFromPolar(User $user): int
    {
        $request = new SubscriptionsListRequest(
            externalCustomerId: (string) $user->id,
            limit: 100,
        );

        $count = 0;

        try {
            foreach ($this->client()->subscriptions->list($request) as $page) {
                foreach ($page->listResourceSubscription?->items ?? [] as $sub) {
                    $data = [
                        'id' => $sub->id,
                        'status' => $sub->status->value,
                        'product_id' => $sub->productId,
                        'current_period_start' => $sub->currentPeriodStart?->format('c'),
                        'current_period_end' => $sub->currentPeriodEnd?->format('c'),
                        'cancel_at_period_end' => $sub->cancelAtPeriodEnd ?? false,
                        'customer' => ['external_id' => (string) $user->id],
                        'metadata' => (array) ($sub->metadata ?? []),
                    ];

                    $this->findOrCreateSubscription($data);
                    $count++;
                }
            }
        } catch (APIException $e) {
            Log::warning('Polar subscription sync failed', [
                'user_id' => $user->id,
                'status' => $e->statusCode,
                'body' => $e->body,
            ]);
            throw $e;
        }

        Log::info('Polar subscription sync complete', [
            'user_id' => $user->id,
            'synced' => $count,
        ]);

        return $count;
    }
}
