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
                $member->portal_token = (string) Str::uuid();
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

    /**
     * Private self-service link for the member (subscription, debt,
     * class booking). Separate from the public door-scanner card link.
     */
    public function portalUrl(): string
    {
        return route('portal.show', $this->portal_token);
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
