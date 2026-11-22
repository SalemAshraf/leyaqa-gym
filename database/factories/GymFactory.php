<?php

namespace Database\Factories;

use App\Models\Gym;
use Illuminate\Database\Eloquent\Factories\Factory;

class GymFactory extends Factory
{
    protected $model = Gym::class;

    public function definition(): array
    {
        return [
            'name' => 'Gym '.fake()->unique()->numberBetween(1, 99999),
            'country' => 'EG',
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'locale' => 'ar',
        ];
    }
}
