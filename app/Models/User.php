<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'google_id',
        'google_token',
        'google_refresh_token',
        'country_code',
        'subscription_tier',
        'active_subscription_id',
        'total_transfers',
        'last_transfer_at',
        'has_used_trial_transfer',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_token',
        'google_refresh_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_transfer_at' => 'datetime',
            'has_used_trial_transfer' => 'boolean',
        ];
    }

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function activeSubscription()
    {
        return $this->belongsTo(UserSubscription::class, 'active_subscription_id');
    }

    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function transfers()
    {
        return $this->hasMany(Transfer::class);
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription && $this->activeSubscription->isActive();
    }

    public function getEffectiveSubscription(): ?UserSubscription
    {
        // Return active subscription if exists, otherwise check for free tier
        if ($this->hasActiveSubscription()) {
            return $this->activeSubscription;
        }

        // For free tier users, create a virtual subscription
        return null;
    }

    public function canMakeTransfer(): bool
    {
        if ($this->subscription_tier === 'free') {
            // Free tier: 5 transfers per month
            $transfersThisMonth = $this->subscriptions()
                ->where('created_at', '>=', now()->startOfMonth())
                ->count();

            return $transfersThisMonth < 5;
        }

        $subscription = $this->getEffectiveSubscription();
        return $subscription ? $subscription->canMakeTransfer() : false;
    }

    public function incrementTransferCount(): void
    {
        // Mark trial as used for free tier users on their first transfer
        if ($this->hasTrialTransferAvailable()) {
            $this->markTrialTransferUsed();
        }

        $this->increment('total_transfers');
        $this->update(['last_transfer_at' => now()]);

        if ($this->hasActiveSubscription()) {
            $this->activeSubscription->incrementTransferCount();
        }
    }

    public function hasTrialTransferAvailable(): bool
    {
        return $this->subscription_tier === 'free' && !$this->has_used_trial_transfer;
    }

    public function markTrialTransferUsed(): void
    {
        $this->update(['has_used_trial_transfer' => true]);
    }

    private const COUNTRY_NAMES = [
        'AF' => 'Afghanistan', 'AL' => 'Albania', 'DZ' => 'Algeria', 'AR' => 'Argentina',
        'AU' => 'Australia', 'AT' => 'Austria', 'BD' => 'Bangladesh', 'BE' => 'Belgium',
        'BR' => 'Brazil', 'CA' => 'Canada', 'CL' => 'Chile', 'CN' => 'China',
        'CO' => 'Colombia', 'CD' => 'Congo (DRC)', 'CZ' => 'Czechia', 'DK' => 'Denmark',
        'EG' => 'Egypt', 'ET' => 'Ethiopia', 'FI' => 'Finland', 'FR' => 'France',
        'DE' => 'Germany', 'GH' => 'Ghana', 'GR' => 'Greece', 'HK' => 'Hong Kong',
        'IN' => 'India', 'ID' => 'Indonesia', 'IR' => 'Iran', 'IQ' => 'Iraq',
        'IE' => 'Ireland', 'IL' => 'Israel', 'IT' => 'Italy', 'JP' => 'Japan',
        'KE' => 'Kenya', 'MY' => 'Malaysia', 'MX' => 'Mexico', 'MA' => 'Morocco',
        'NL' => 'Netherlands', 'NZ' => 'New Zealand', 'NG' => 'Nigeria', 'NO' => 'Norway',
        'PK' => 'Pakistan', 'PE' => 'Peru', 'PH' => 'Philippines', 'PL' => 'Poland',
        'PT' => 'Portugal', 'RO' => 'Romania', 'RU' => 'Russia', 'SA' => 'Saudi Arabia',
        'SG' => 'Singapore', 'ZA' => 'South Africa', 'KR' => 'South Korea', 'ES' => 'Spain',
        'SE' => 'Sweden', 'CH' => 'Switzerland', 'TW' => 'Taiwan', 'TZ' => 'Tanzania',
        'TH' => 'Thailand', 'TR' => 'Turkey', 'UA' => 'Ukraine', 'AE' => 'UAE',
        'GB' => 'United Kingdom', 'US' => 'United States', 'VN' => 'Vietnam',
    ];

    public function getCountryNameAttribute(): ?string
    {
        if (!$this->country_code) {
            return null;
        }
        return self::COUNTRY_NAMES[$this->country_code] ?? $this->country_code;
    }

    public function isFromNigeria(): bool
    {
        return $this->country_code === 'NG';
    }

    public function getPreferredPaymentProvider(): string
    {
        return $this->isFromNigeria() ? 'paystack' : 'lemonsqueezy';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function makeAdmin(): void
    {
        $this->update(['role' => 'admin']);
    }

    public function removeAdmin(): void
    {
        $this->update(['role' => 'user']);
    }
}
