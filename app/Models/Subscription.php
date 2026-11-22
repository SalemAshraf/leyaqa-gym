<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use BelongsToGym, HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_FROZEN = 'frozen';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'member_id', 'plan_id', 'starts_at', 'ends_at', 'status',
        'sessions_remaining', 'freeze_days_used', 'price', 'notes',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'price' => 'decimal:2',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function freezes(): HasMany
    {
        return $this->hasMany(Freeze::class);
    }

    public function paidAmount(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function balance(): float
    {
        return round((float) $this->price - $this->paidAmount(), 2);
    }

    /** Effective status as of a date: stored status, with date-based expiry applied. */
    public function effectiveStatus(?Carbon $on = null): string
    {
        $on ??= now($this->gym?->timezone ?? config('app.timezone'));

        if ($this->status === self::STATUS_CANCELLED) {
            return self::STATUS_CANCELLED;
        }

        if ($this->ends_at && $on->greaterThan($this->ends_at->endOfDay())) {
            return self::STATUS_EXPIRED;
        }

        if ($this->status === self::STATUS_FROZEN) {
            return self::STATUS_FROZEN;
        }

        if ($on->lessThan($this->starts_at->startOfDay())) {
            return 'upcoming';
        }

        return self::STATUS_ACTIVE;
    }

    public function canEnter(?Carbon $on = null): array
    {
        $status = $this->effectiveStatus($on);

        if ($status !== self::STATUS_ACTIVE) {
            return [false, $status];
        }

        if ($this->plan->isSessions() && ($this->sessions_remaining ?? 0) <= 0) {
            return [false, 'no_sessions_left'];
        }

        return [true, null];
    }

    public function daysLeft(?Carbon $on = null): ?int
    {
        if (! $this->ends_at) {
            return null;
        }
        $on ??= now();

        return (int) $on->startOfDay()->diffInDays($this->ends_at, false);
    }
}
