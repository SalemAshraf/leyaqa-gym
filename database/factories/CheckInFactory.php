<?php

namespace Database\Factories;

use App\Models\CheckIn;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

class CheckInFactory extends Factory
{
    protected $model = CheckIn::class;

    public function definition(): array
    {
        return [
            'gym_id' => fn (array $attrs) => Member::find($attrs['member_id'])->gym_id,
            'member_id' => Member::factory(),
            'checked_in_at' => now(),
            'method' => 'manual',
            'result' => 'allowed',
        ];
    }
}
