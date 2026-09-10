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
        'signup_referrer',
        'signup_landing',
        'subscription_tier',
        'active_subscription_id',
        'total_transfers',
        'last_transfer_at',
        'has_used_trial_transfer',
        'check_in_email_sent',
        'email_opt_out',
        'winback_email_sent',
        'feature_email_sent',
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
            'check_in_email_sent' => 'boolean',
            'email_opt_out' => 'boolean',
            'winback_email_sent' => 'boolean',
            'feature_email_sent' => 'boolean',
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

    public function driveFolders()
    {
        return $this->hasMany(DriveFolder::class);
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription && $this->activeSubscription->isActive();
    }

    public function downgradeToFree(): void
    {
        $this->update([
            'active_subscription_id' => null,
            'subscription_tier' => 'free',
        ]);
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
        if ($this->onSharedAllowance) {
            // The sharer already spent a share on this one; charging the
            // recipient too would burn an allowance they never agreed to spend.
            return;
        }

        // Trial consumption is handled atomically at transfer admission
        // (TransferController::resolveFileSizeLimit), not here, so that a
        // small transfer never burns the one-time large-file trial.
        $this->increment('total_transfers');
        $this->update(['last_transfer_at' => now()]);

        if ($this->hasActiveSubscription()) {
            $this->activeSubscription->incrementTransferCount();
        }
    }

    /**
     * Set for the life of one request when this transfer is running on a share
     * someone else paid for. Declared rather than dynamic so Eloquent's
     * attribute magic never sees it and it cannot reach a query.
     */
    public bool $onSharedAllowance = false;

    /** Whose plan decides the limits for this request, when not this user's. */
    public ?self $planFrom = null;

    /**
     * The raw referrer read as a channel. Derived rather than stored: a channel
     * guessed at signup time cannot be revised, and the rules do change --
     * chatgpt.com was not a referrer worth naming a year ago.
     */
    public function signupChannel(): string
    {
        if (str_starts_with((string) $this->signup_landing, '/share/')) {
            return 'Shared with them';
        }

        if (blank($this->signup_referrer)) {
            return 'Direct or unknown';
        }

        $host = strtolower((string) parse_url($this->signup_referrer, PHP_URL_HOST));

        return match (true) {
            str_contains($host, 'google.') => 'Google search',
            str_contains($host, 'bing.') => 'Bing',
            str_contains($host, 'duckduckgo.') => 'DuckDuckGo',
            str_contains($host, 'chatgpt.') || str_contains($host, 'openai.') => 'ChatGPT',
            str_contains($host, 'perplexity.') => 'Perplexity',
            str_contains($host, 'claude.') => 'Claude',
            str_contains($host, 'facebook.') || str_contains($host, 'fb.') => 'Facebook',
            str_contains($host, 'instagram.') => 'Instagram',
            str_contains($host, 'reddit.') => 'Reddit',
            str_contains($host, 'youtube.') => 'YouTube',
            str_contains($host, 'linkedin.') => 'LinkedIn',
            str_contains($host, 't.co') || str_contains($host, 'twitter.') || str_contains($host, 'x.com') => 'X',
            str_contains($host, 'producthunt.') => 'Product Hunt',
            str_contains($host, 'wetodrive.') => 'Returning visitor',
            $host === '' => 'Direct or unknown',
            default => $host,
        };
    }

    public function transferShares()
    {
        return $this->hasMany(TransferShare::class);
    }

    /**
     * How many shares this plan allows. Free is once ever, matching how the
     * one-time trial transfer works; paid plans get theirs back each period.
     */
    public function shareLimit(): int
    {
        return (int) ($this->hasActiveSubscription()
            ? ($this->activeSubscription->subscriptionPlan->share_limit ?? 0)
            : (SubscriptionPlan::where('slug', 'free')->value('share_limit') ?? 0));
    }

    public function sharesUsed(): int
    {
        $used = $this->transferShares()->countsAgainstAllowance();

        // Paid allowances reset with the billing period; the free one never does.
        // resetTransferCount() pushes period_resets_at a month out, so the period
        // in progress began a month before it — the same clock transfers use.
        if ($this->hasActiveSubscription()) {
            $sub = $this->activeSubscription;
            $periodStart = $sub->period_resets_at
                ? $sub->period_resets_at->copy()->subMonth()
                : $sub->started_at;

            if ($periodStart) {
                $used->where('created_at', '>=', $periodStart);
            }
        }

        return $used->count();
    }

    public function sharesRemaining(): int
    {
        return max($this->shareLimit() - $this->sharesUsed(), 0);
    }

    public function canShare(): bool
    {
        return $this->sharesRemaining() > 0;
    }

    public function hasTrialTransferAvailable(): bool
    {
        return $this->subscription_tier === 'free' && !$this->has_used_trial_transfer;
    }

    public function markTrialTransferUsed(): void
    {
        $this->update(['has_used_trial_transfer' => true]);
    }

    /**
     * Atomically claim the one-time trial transfer. Returns true only if THIS
     * call won it — a single conditional UPDATE so concurrent transfers can't
     * both pass the file-size check on the same unused trial.
     */
    public function claimTrialTransfer(): bool
    {
        $won = static::where('id', $this->id)
            ->where('subscription_tier', 'free')
            ->where('has_used_trial_transfer', false)
            ->update(['has_used_trial_transfer' => true]) === 1;

        if ($won) {
            $this->has_used_trial_transfer = true; // keep in-memory model in sync
        }

        return $won;
    }

    /**
     * Return the trial to the user (e.g. when a claimed transfer then fails),
     * so a failed transfer never burns the one-time allowance.
     */
    public function releaseTrialTransfer(): void
    {
        static::where('id', $this->id)->update(['has_used_trial_transfer' => false]);
        $this->has_used_trial_transfer = false;
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
        return $this->isFromNigeria() ? 'paystack' : 'polar';
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
