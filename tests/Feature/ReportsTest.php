<?php

use App\Models\CheckIn;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;

it('shows reports to roles with reports.view with a default current-month range', function () {
    [$gym, $owner] = makeOwner();
    $gym->update(['timezone' => 'Africa/Cairo', 'currency' => 'EGP']);

    $plan = Plan::factory()->create(['gym_id' => $gym->id]);
    $member = Member::factory()->create(['gym_id' => $gym->id]);
    $sub = Subscription::factory()->create([
        'gym_id' => $gym->id, 'member_id' => $member->id, 'plan_id' => $plan->id,
        'status' => 'active', 'starts_at' => today(), 'ends_at' => today()->addMonth(),
    ]);
    Payment::factory()->create(['gym_id' => $gym->id, 'subscription_id' => $sub->id, 'amount' => 500, 'method' => 'cash', 'paid_at' => now()]);

    foreach ([$owner, makeStaff($gym)] as $user) {
        $this->actingAs($user)->get('/reports')->assertOk()->assertInertia(
            fn ($page) => $page
                ->where('filters.from', now('Africa/Cairo')->startOfMonth()->toDateString())
                ->where('filters.to', now('Africa/Cairo')->toDateString())
                ->where('kpis.revenue', 500)
                ->where('kpis.payments_count', 1)
                ->has('byMethod', 1)
                ->where('byMethod.0.label', 'cash')
                ->where('byMethod.0.value', 500)
                ->has('revenueByDay', (int) now('Africa/Cairo')->format('j'))
                ->has('checkinsByHour', 24)
                ->has('latestPayments', 1)
                ->where('latestPayments.0.member', $member->name)
        );
    }
});

it('filters every metric by the requested from/to range', function () {
    [$gym, $owner] = makeOwner();
    $gym->update(['timezone' => 'Africa/Cairo', 'currency' => 'EGP']);

    $plan = Plan::factory()->create(['gym_id' => $gym->id]);
    $member = Member::factory()->create(['gym_id' => $gym->id, 'joined_at' => '2026-11-10']);
    $sub = Subscription::factory()->create([
        'gym_id' => $gym->id, 'member_id' => $member->id, 'plan_id' => $plan->id,
        'status' => 'active', 'starts_at' => '2026-11-01', 'ends_at' => '2026-12-01',
    ]);

    // Inside the range (Nov 10 - Nov 20).
    Payment::factory()->create(['gym_id' => $gym->id, 'subscription_id' => $sub->id, 'amount' => 300, 'method' => 'cash', 'paid_at' => '2026-11-12 10:00:00']);
    Payment::factory()->create(['gym_id' => $gym->id, 'subscription_id' => $sub->id, 'amount' => 200, 'method' => 'card', 'paid_at' => '2026-11-12 12:00:00']);
    // Outside the range.
    Payment::factory()->create(['gym_id' => $gym->id, 'subscription_id' => $sub->id, 'amount' => 999, 'method' => 'cash', 'paid_at' => '2026-11-02 10:00:00']);

    CheckIn::factory()->create(['gym_id' => $gym->id, 'member_id' => $member->id, 'checked_in_at' => '2026-11-15 18:00:00', 'result' => 'allowed']);
    CheckIn::factory()->create(['gym_id' => $gym->id, 'member_id' => $member->id, 'checked_in_at' => '2026-11-16 19:00:00', 'result' => 'denied']);
    CheckIn::factory()->create(['gym_id' => $gym->id, 'member_id' => $member->id, 'checked_in_at' => '2026-11-01 10:00:00', 'result' => 'allowed']);

    $this->actingAs($owner)->get('/reports?from=2026-11-10&to=2026-11-20')->assertOk()->assertInertia(
        fn ($page) => $page
            ->where('filters.from', '2026-11-10')
            ->where('filters.to', '2026-11-20')
            ->where('kpis.revenue', 500)
            ->where('kpis.payments_count', 2)
            ->where('kpis.checkins_allowed', 1)
            ->where('kpis.checkins_denied', 1)
            ->where('kpis.unique_members', 1)
            ->has('revenueByDay', 11)
            ->has('checkinsByDay', 11)
            ->has('byMethod', 2)
            ->has('latestPayments', 2)
            ->where('topAttendees.0.visits', 1)
    );
});

it('swaps a reversed range instead of failing', function () {
    [$gym, $owner] = makeOwner();

    $this->actingAs($owner)->get('/reports?from=2026-11-20&to=2026-11-10')->assertOk()->assertInertia(
        fn ($page) => $page
            ->where('filters.from', '2026-11-10')
            ->where('filters.to', '2026-11-20')
            ->has('revenueByDay', 11)
    );
});

it('rejects malformed range dates', function () {
    [$gym, $owner] = makeOwner();

    $this->actingAs($owner)->get('/reports?from=not-a-date')->assertSessionHasErrors('from');
});

it('redirects guests from reports to login', function () {
    $this->get('/reports')->assertRedirect('/login');
});
