<?php

namespace App\Models\Concerns;

use App\Models\Gym;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToGym
{
    protected static function bootBelongsToGym(): void
    {
        static::addGlobalScope('gym', function (Builder $query) {
            $user = Auth::user();
            if ($user && $user->gym_id) {
                $query->where($query->getModel()->getTable().'.gym_id', $user->gym_id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->gym_id) && Auth::user()?->gym_id) {
                $model->gym_id = Auth::user()->gym_id;
            }
        });
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }
}
