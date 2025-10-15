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

    public function isActive(): bool
    {
        return $this->status === 'active' &&
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

    public function scopeForProvider($query, string $provider)
    {
        return $query->where('payment_provider', $provider);
    }
}
