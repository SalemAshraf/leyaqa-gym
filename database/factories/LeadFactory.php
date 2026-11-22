<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake('ar_EG')->name(),
            'phone' => '01' . fake()->numerify('#########'),
            'source' => 'instagram',
            'status' => 'new',
        ];
    }
}
