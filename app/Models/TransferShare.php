<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TransferShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'token', 'wetransfer_url', 'title', 'total_size',
        'file_count', 'recipient_email', 'claimed_by_user_id', 'claimed_at',
        'revoked_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'claimed_at' => 'datetime',
            'revoked_at' => 'datetime',
            'expires_at' => 'datetime',
            'total_size' => 'integer',
            'file_count' => 'integer',
        ];
    }

    /**
     * WeTransfer links last 7 days, so a share cannot usefully outlive one.
     * Dated from creation because we cannot see when the link itself was made.
     */
    public const LIFETIME_DAYS = 7;

    public static function mint(User $sharer, string $url, array $meta = []): self
    {
        return static::create([
            'user_id' => $sharer->id,
            // 64 hex chars. The link is the credential, so it has to be
            // unguessable on its own.
            'token' => Str::random(48),
            'wetransfer_url' => $url,
            'title' => $meta['title'] ?? null,
            'total_size' => $meta['total_size'] ?? null,
            'file_count' => $meta['file_count'] ?? null,
            'recipient_email' => $meta['recipient_email'] ?? null,
            'expires_at' => now()->addDays(self::LIFETIME_DAYS),
        ]);
    }

    public function sharer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function claimedBy()
    {
        return $this->belongsTo(User::class, 'claimed_by_user_id');
    }

    public function isClaimed(): bool
    {
        return $this->claimed_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isClaimable(): bool
    {
        return ! $this->isClaimed() && ! $this->isExpired() && ! $this->isRevoked();
    }

    /**
     * Why this share cannot be claimed, for the recipient's benefit. A dead link
     * should say which kind of dead it is, not just fail.
     */
    public function unclaimableReason(): ?string
    {
        return match (true) {
            $this->isRevoked() => 'The sender cancelled this share.',
            $this->isClaimed() => 'This share has already been used.',
            $this->isExpired() => 'This share has expired. WeTransfer links only last 7 days, so ask the sender for a new one.',
            default => null,
        };
    }

    /**
     * Claim atomically, so two people opening the same link cannot both win it.
     */
    public function claimFor(User $recipient): bool
    {
        return static::where('id', $this->id)
            ->whereNull('claimed_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->update([
                'claimed_at' => now(),
                'claimed_by_user_id' => $recipient->id,
            ]) === 1;
    }

    /**
     * Shares that count against the sharer's allowance: anything still live or
     * already used. A revoked or expired-unclaimed share gives its slot back.
     */
    public function scopeCountsAgainstAllowance($query)
    {
        return $query->whereNull('revoked_at')
            ->where(fn ($q) => $q->whereNotNull('claimed_at')->orWhere('expires_at', '>', now()));
    }
}
