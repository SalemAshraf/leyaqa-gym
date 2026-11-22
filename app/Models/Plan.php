<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use BelongsToGym, HasFactory;

    public const TYPE_DURATION = 'duration';
    public const TYPE_SESSIONS = 'sessions';

    protected $fillable = [
        'name', 'type', 'duration_days', 'sessions_count', 'price', 'freeze_days_allowed', 'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isDuration(): bool
    {
        return $this->type === self::TYPE_DURATION;
    }

    public function isSessions(): bool
    {
        return $this->type === self::TYPE_SESSIONS;
    }
}
