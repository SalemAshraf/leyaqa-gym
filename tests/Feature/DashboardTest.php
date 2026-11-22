<?php

use App\Models\CheckIn;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;

it('returns trend, hourly, plan-mix and recent check-in data', function () {
    [$gym, $owner] = makeOwner();
    $gym->update(['timezone' => 'Africa/Cairo', 'currency' => 'EGP']);

    $plan = Plan::factory()->create(['gym_id' => $gym->id, 'name' => 'شهري']);
    $member = Member::factory()->create(['gym_id' => $gym->id, 'name' => 'عضو تجريبي']);
    $sub = Subscription::factory()->create([
        'gym_id' => $gym->id, 'member_id' => $member->id, 'plan_id' => $plan->id,
        'status' => 'active', 'starts_at' => today()->subDays(3), 'ends_at' => today()->addDays(20),
    ]);
    Payment::factory()->create([
        'gym_id' => $gym->id, 'subscription_id' => $sub->id, 'amount' => 800,
        'paid_at' => now(),
    ]);
    Payment::factory()->create([
        'gym_id' => $gym->id, 'subscription_id' => $sub->id, 'amount' => 300,
        'paid_at' => now()->subMonths(2)->startOfMonth()->addDays(3),
    ]);

    $this->actingAs($owner)->get('/dashboard')->assertOk()->assertInertia(
        fn ($page) => $page
            ->has('revenueTrend', 6)
            ->has('checkinsByHour', 24)
            ->has('planMix', 1)
            ->where('planMix.0.label', 'شهري')
            ->where('stats.revenue_month', 800)
            ->where('stats.active_subscriptions', 1)
            ->where('revenueTrend.5.revenue', 800)
            ->where('revenueTrend.3.revenue', 300)
    );
});

it('counts gym-local-day check-ins stored across the UTC day boundary', function () {
    [$gym, $owner] = makeOwner();
    $gym->update(['timezone' => 'Africa/Cairo']); // UTC+2, no DST

    $member = Member::factory()->create(['gym_id' => $gym->id]);
    // 01:30 in Cairo today = 23:30 UTC yesterday. whereDate on the stored
    // UTC value would file this under yesterday; the gym day must win.
    $cairoEarly = now('Africa/Cairo')->setTime(1, 30);
    CheckIn::factory()->create([
        'gym_id' => $gym->id, 'member_id' => $member->id,
        'checked_in_at' => $cairoEarly->copy()->setTimezone('UTC'),
        'result' => 'allowed',
    ]);

    $this->actingAs($owner)->get('/dashboard')->assertInertia(
        fn ($page) => $page
            ->where('stats.checkins_today', 1)
            ->where('checkinsByHour.1.count', 1)
    );

    // The check-in page shows the same gym-local moment, not the UTC one.
    $this->actingAs($owner)->get('/check-in')->assertInertia(
        fn ($page) => $page->where(
            'today.0.checked_in_at',
            $cairoEarly->format('Y-m-d H:i:s')
        )
    );
});

it('guides first-run setup and disappears when done or dismissed', function () {
    [$gym, $owner] = makeOwner();

    // Fresh gym: guide visible with no steps done.
    $this->actingAs($owner)->get('/dashboard')->assertInertia(
        fn ($page) => $page->where('setup.done', 0)->where('setup.total', 6)->where('setup.steps.0.done', false)
    );

    // Completing real state flips steps.
    $gym->update(['phone' => '01000000000']);
    \App\Models\Plan::factory()->create(['gym_id' => $gym->id]);
    $this->actingAs($owner->fresh())->get('/dashboard')->assertInertia(
        fn ($page) => $page->where('setup.done', 2)->where('setup.steps.0.done', true)->where('setup.steps.1.done', true)
    );

    // Dismissal hides it even while steps remain.
    $this->actingAs($owner)->post('/setup/dismiss')->assertSessionHasNoErrors();
    $this->actingAs($owner->fresh())->get('/dashboard')->assertInertia(
        fn ($page) => $page->where('setup', null)
    );
});
