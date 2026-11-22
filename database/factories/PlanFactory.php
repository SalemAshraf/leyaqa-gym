<?php

namespace Database\Factories;

use App\Models\Gym;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'gym_id' => Gym::factory(),
            'name' => 'Monthly '.fake()->unique()->numberBetween(1, 99999),
            'type' => Plan::TYPE_DURATION,
            'duration_days' => 30,
            'price' => 800,
            'freeze_days_allowed' => 7,
            'is_active' => true,
        ];
    }

    public function sessions(int $count = 12): static
    {
        return $this->state(fn () => [
            'type' => Plan::TYPE_SESSIONS,
            'duration_days' => null,
            'sessions_count' => $count,
        ]);
    }
}
