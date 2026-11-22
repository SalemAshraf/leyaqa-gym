<?php

namespace Database\Factories;

use App\Models\GymClass;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassBookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gym_id' => fn (array $attrs) => GymClass::withoutGlobalScopes()->find($attrs['gym_class_id'])->gym_id,
            'class_date' => now()->toDateString(),
            'status' => 'booked',
        ];
    }
}
