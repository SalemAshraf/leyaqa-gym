<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformInvoice extends Model
{
    protected $fillable = ['gym_id', 'platform_plan_id', 'amount_cents', 'currency', 'period_starts_at', 'period_ends_at', 'status', 'paid_at', 'note'];

    protected $casts = ['period_starts_at' => 'date', 'period_ends_at' => 'date', 'paid_at' => 'datetime'];

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlatformPlan::class, 'platform_plan_id');
    }
}
