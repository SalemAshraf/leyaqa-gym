<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A due (never auto-charged) invoice for a subscription renewal cycle.
 */
class RenewalInvoice extends Model
{
    use BelongsToGym, HasFactory;

    protected $fillable = ['gym_id', 'member_id', 'subscription_id', 'amount', 'status', 'due_date', 'collected_at', 'created_by', 'paid_amount', 'paid_method', 'cancelled_at', 'cancelled_by'];

    protected $casts = ['due_date' => 'date', 'collected_at' => 'datetime', 'cancelled_at' => 'datetime', 'amount' => 'float', 'paid_amount' => 'float'];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function scopeDue($query)
    {
        return $query->where('status', 'due');
    }
}
