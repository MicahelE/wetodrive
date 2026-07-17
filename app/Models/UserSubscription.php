<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'payment_provider',
        'provider_subscription_id',
        'status',
        'started_at',
        'expires_at',
        'cancelled_at',
        'transfers_used',
        'period_resets_at',
        'amount_paid',
        'currency',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'period_resets_at' => 'datetime',
        'amount_paid' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * Whether the subscription grants access right now.
     *
     * A cancelled subscription still counts until it expires: cancelling means
     * "don't renew me", not "refund the month I already paid for". Access ends
     * at expires_at, when subscriptions:expire retires it.
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'cancelled'], true) &&
               ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Is this subscription due to stop at the end of the paid period?
     *
     * Our own status is the reliable signal: metadata only learns
     * cancel_at_period_end from a webhook, so it is still false right after a
     * cancellation. Trusting metadata alone means an upgrade skips the uncancel
     * and the customer gets shut off anyway.
     */
    public function isSetToCancel(): bool
    {
        return $this->isCancelled() || ($this->metadata['cancel_at_period_end'] ?? false);
    }

    public function getRemainingTransfers(): ?int
    {
        if ($this->subscriptionPlan->isUnlimitedTransfers()) {
            return null; // unlimited
        }

        return max(0, $this->subscriptionPlan->transfer_limit - $this->transfers_used);
    }

    public function canMakeTransfer(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        // Reset transfers if period has reset
        if ($this->period_resets_at && $this->period_resets_at->isPast()) {
            $this->resetTransferCount();
        }

        return $this->subscriptionPlan->isUnlimitedTransfers() || $this->getRemainingTransfers() > 0;
    }

    public function incrementTransferCount(): void
    {
        $this->increment('transfers_used');
    }

    public function resetTransferCount(): void
    {
        $this->update([
            'transfers_used' => 0,
            'period_resets_at' => now()->addMonth(),
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function expire(): void
    {
        $this->update(['status' => 'expired']);
    }

    public function getFormattedAmount(): string
    {
        return $this->currency === 'NGN'
            ? '₦' . number_format($this->amount_paid, 0)
            : '$' . number_format($this->amount_paid, 2);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCurrentlyActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function scopeForProvider($query, string $provider)
    {
        return $query->where('payment_provider', $provider);
    }
}
