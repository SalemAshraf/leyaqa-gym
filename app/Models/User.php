<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'gym_id', 'phone', 'locale',
            'branch_id',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_platform_admin' => 'boolean',
        ];
    }

    /**
     * Platform-area access: a DB grant on the user row (provisioned by
     * `platform:admin`), with the env-listed emails kept as a fallback.
     * Deliberately NOT mass-assignable.
     */
    public function isPlatformAdmin(): bool
    {
        return $this->is_platform_admin || in_array($this->email, config('platform.admin_emails', []), true);
    }

    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function isOwner(): bool
    {
        return $this->hasRole('owner');
    }
}
