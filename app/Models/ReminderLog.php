<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderLog extends Model
{
    use BelongsToGym;

    protected $fillable = ['gym_id', 'member_id', 'kind', 'reference_date', 'channel', 'body'];

    protected $casts = ['reference_date' => 'date'];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
