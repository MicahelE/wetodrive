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
        $this->increment('total_transfers');
        $this->update(['last_transfer_at' => now()]);

        if ($this->hasActiveSubscription()) {
            $this->activeSubscription->incrementTransferCount();
        }
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
