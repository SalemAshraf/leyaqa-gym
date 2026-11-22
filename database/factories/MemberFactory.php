<?php

namespace Database\Factories;

use App\Models\Gym;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'gym_id' => Gym::factory(),
            'name' => fake()->name(),
            'phone' => '01'.fake()->unique()->numerify('#########'),
            'status' => 'active',
        ];
    }
}
