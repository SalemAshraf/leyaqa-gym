<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GymClass extends Model
{
    use BelongsToGym, HasFactory;

    protected $fillable = ['name', 'coach', 'weekday', 'start_time', 'duration_minutes', 'capacity', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'weekday' => 'integer'];

    public function bookings(): HasMany
    {
        return $this->hasMany(ClassBooking::class);
    }

    public function bookedCount(string $date): int
    {
        return $this->bookings()->whereDate('class_date', $date)->where('status', 'booked')->count();
    }

    public function waitlistCount(string $date): int
    {
        return $this->bookings()->whereDate('class_date', $date)->where('status', 'waitlisted')->count();
    }

    public function hasSpots(string $date): bool
    {
        return $this->bookedCount($date) < $this->capacity;
    }
}
