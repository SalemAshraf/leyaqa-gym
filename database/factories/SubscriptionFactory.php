<?php

namespace Database\Factories;

use App\Models\Gym;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        $start = Carbon::today();

        return [
            'gym_id' => Gym::factory(),
            'member_id' => fn (array $attrs) => Member::factory()->create(['gym_id' => $attrs['gym_id']])->id,
            'plan_id' => fn (array $attrs) => Plan::factory()->create(['gym_id' => $attrs['gym_id']])->id,
            'starts_at' => $start->toDateString(),
            'ends_at' => $start->copy()->addDays(29)->toDateString(),
            'status' => Subscription::STATUS_ACTIVE,
            'price' => 800,
        ];
    }
}
