<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A bounded portal session (web or API), minted by exchanging the member's
 * single-use bootstrap link. Only the token hash is stored.
 */
class MemberPortalSession extends Model
{
    use BelongsToGym;

    public const TTL_DAYS = 30;

    protected $fillable = ['gym_id', 'member_id', 'token_hash', 'label', 'last_used_at', 'expires_at', 'revoked_at'];

    protected $casts = ['last_used_at' => 'datetime', 'expires_at' => 'datetime', 'revoked_at' => 'datetime'];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** Mint a session; returns [session, plaintext token shown once]. */
    public static function mint(Member $member, ?string $label = null): array
    {
        $plain = Str::random(48);
        $session = static::create([
            'gym_id' => $member->gym_id,
            'member_id' => $member->id,
            'token_hash' => hash('sha256', $plain),
            'label' => $label,
            'expires_at' => now()->addDays(self::TTL_DAYS),
        ]);

        return [$session, $plain];
    }

    public static function findValidByToken(?string $plain): ?self
    {
        if (empty($plain)) {
            return null;
        }

        $session = static::withoutGlobalScopes()
            ->where('token_hash', hash('sha256', $plain))
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();
        $session?->forceFill(['last_used_at' => now()])->save();

        return $session;
    }

    public function isValid(): bool
    {
        return $this->revoked_at === null && $this->expires_at?->isFuture();
    }

    public function touchLastUsed(): void
    {
        $this->forceFill(['last_used_at' => now()])->save();
    }
}
