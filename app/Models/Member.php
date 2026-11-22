<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Member extends Model
{
    use BelongsToGym, HasFactory;

    protected $fillable = [
        'name', 'phone', 'email', 'gender', 'birthdate', 'notes', 'status', 'joined_at', 'branch_id',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'joined_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Member $member) {
            if (empty($member->qr_token)) {
                $member->qr_token = (string) Str::uuid();
            }
            if (empty($member->portal_token)) {
                // Only the hash is stored; staff mint a usable link via rotation.
                $member->portal_token = static::hashPortalToken((string) Str::uuid());
            }
            if (empty($member->joined_at)) {
                $member->joined_at = now()->toDateString();
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Staff pinned to a branch see only that branch's members.
     * Owners and unpinned users see everyone.
     */
    public function scopeVisibleTo($query, User $user): void
    {
        if ($user->branch_id && ! $user->hasRole('owner')) {
            $query->where('branch_id', $user->branch_id);
        }
    }

    public function accessibleBy(User $user): bool
    {
        return ! ($user->branch_id && ! $user->hasRole('owner') && $this->branch_id !== $user->branch_id);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    public static function hashPortalToken(string $plain): string
    {
        return hash('sha256', $plain);
    }

    /**
     * Resolve a member from the plaintext portal token carried by the private
     * link / API bearer. The door QR token is never accepted here.
     */
    public static function findByPortalToken(?string $plain): ?self
    {
        if (empty($plain)) {
            return null;
        }

        return static::withoutGlobalScopes()
            ->where('portal_token', static::hashPortalToken($plain))
            ->first();
    }

    /**
     * Mint a new private portal secret; the old link stops working at once.
     * The plaintext is returned once - only its hash is ever stored.
     */
    public function rotatePortalToken(): string
    {
        $plain = Str::random(48);
        $this->forceFill(['portal_token' => static::hashPortalToken($plain)])->save();

        return $plain;
    }

    public function currentSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->whereIn('status', ['active', 'frozen'])
            ->latest('starts_at')
            ->first();
    }

    public function outstandingBalance(): float
    {
        $total = 0.0;
        foreach ($this->subscriptions()->with('payments')->get() as $subscription) {
            $total += $subscription->balance();
        }

        return round(max($total, 0), 2);
    }
}
