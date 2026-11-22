<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gym extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'country', 'currency', 'timezone', 'locale', 'platform_plan_id', 'platform_status', 'trial_ends_at', 'platform_renews_at', 'setup_dismissed_at', 'reminders_enabled', 'reminder_days_before', 'portal_link_days'];

    protected $casts = ['trial_ends_at' => 'date', 'platform_renews_at' => 'date', 'setup_dismissed_at' => 'datetime', 'reminders_enabled' => 'boolean', 'reminder_days_before' => 'integer', 'portal_link_days' => 'integer'];

    /** Days left in the free trial (0 when expired or not trialing). */
    public function trialDaysLeft(): int
    {
        if ($this->platform_status !== 'trialing' || ! $this->trial_ends_at) {
            return 0;
        }

        return max(0, (int) now($this->timezone ?: 'UTC')->startOfDay()->diffInDays($this->trial_ends_at, false));
    }

    /** Whether the gym may use the app (trial running, paid, or inside grace). */
    public function platformAccessible(): bool
    {
        return match ($this->platform_status) {
            'trialing' => $this->trialDaysLeft() > 0,
            'active' => ! $this->platform_renews_at
                || now($this->timezone ?: 'UTC')->startOfDay()->lte($this->platform_renews_at->copy()->addDays((int) config('platform.grace_days', 0))),
            default => false,
        };
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function platformInvoices(): HasMany
    {
        return $this->hasMany(PlatformInvoice::class);
    }

    public function platformPlan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlatformPlan::class, 'platform_plan_id');
    }
}
