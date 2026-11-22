<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GymClassFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'CrossFit',
            'coach' => 'كابتن ' . fake('ar_EG')->firstName(),
            'weekday' => now()->dayOfWeek,
            'start_time' => '18:00',
            'duration_minutes' => 60,
            'capacity' => 2,
            'is_active' => true,
        ];
    }
}
