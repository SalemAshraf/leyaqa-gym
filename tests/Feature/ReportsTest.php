<?php

use App\Models\Member;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;

it('shows reports to roles with reports.view and breaks down money and attendance', function () {
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
                ->has('revenueTrend', 12)
                ->has('checkinsPerDay', 30)
                ->has('byMethod', 1)
                ->where('byMethod.0.label', 'cash')
                ->where('byMethod.0.value', 500)
                ->where('kpis.revenue_month', 500)
        );
    }

});

it('redirects guests from reports to login', function () {
    $this->get('/reports')->assertRedirect('/login');
});
