<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price_ngn',
        'price_usd',
        'transfer_limit',
        'max_file_size',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_ngn' => 'decimal:2',
        'price_usd' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
        'max_file_size' => 'integer',
    ];

    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function getPriceForCountry(string $countryCode): float
    {
        return $countryCode === 'NG' ? $this->price_ngn : $this->price_usd;
    }

    public function getCurrencyForCountry(string $countryCode): string
    {
        return $countryCode === 'NG' ? 'NGN' : 'USD';
    }

    public function getFormattedPriceForCountry(string $countryCode): string
    {
        $price = $this->getPriceForCountry($countryCode);
        $currency = $this->getCurrencyForCountry($countryCode);

        return $currency === 'NGN'
            ? '₦' . number_format($price, 0)
            : '$' . number_format($price, 0);
    }

    public function isUnlimitedTransfers(): bool
    {
        return is_null($this->transfer_limit);
    }

    public function getFormattedFileSize(): string
    {
        $bytes = $this->max_file_size;

        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024)) . 'GB';
        }

        return round($bytes / (1024 * 1024)) . 'MB';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
