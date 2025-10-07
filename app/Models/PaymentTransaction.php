<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'user_subscription_id',
        'provider',
        'provider_reference',
        'type',
        'status',
        'amount',
        'currency',
        'payment_method',
        'provider_response',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'provider_response' => 'array',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userSubscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function markAsSuccessful(?array $providerResponse = null): void
    {
        $this->update([
            'status' => 'success',
            'paid_at' => now(),
            'provider_response' => $providerResponse ?? $this->provider_response,
        ]);
    }

    public function markAsFailed(?array $providerResponse = null): void
    {
        $this->update([
            'status' => 'failed',
            'provider_response' => $providerResponse ?? $this->provider_response,
        ]);
    }

    public function markAsRefunded(?array $providerResponse = null): void
    {
        $this->update([
            'status' => 'refunded',
            'provider_response' => $providerResponse ?? $this->provider_response,
        ]);
    }

    public function getFormattedAmount(): string
    {
        return $this->currency === 'NGN'
            ? '₦' . number_format($this->amount, 0)
            : '$' . number_format($this->amount, 2);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
