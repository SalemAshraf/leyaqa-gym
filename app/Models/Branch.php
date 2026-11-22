<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use BelongsToGym, HasFactory;

    protected $fillable = ['name', 'address', 'phone'];

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
