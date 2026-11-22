<?php

use App\Models\Member;
use App\Models\Plan;

it('keeps plan management away from staff', function () {
    [$gym, $owner] = makeOwner();
    $staff = makeStaff($gym);

    $this->actingAs($staff)->get('/plans')->assertForbidden();
    $this->actingAs($staff)->post('/plans', [
        'name' => 'X', 'type' => 'duration', 'duration_days' => 30, 'price' => 100,
    ])->assertForbidden();

    $this->actingAs($owner)->get('/plans')->assertOk();
    $this->actingAs($owner)->post('/plans', [
        'name' => 'شهري', 'type' => 'duration', 'duration_days' => 30, 'price' => 800, 'freeze_days_allowed' => 7,
    ])->assertRedirect();
    expect(Plan::count())->toBe(1);
});

it('keeps settings and staff management owner-only', function () {
    [$gym, $owner] = makeOwner();
    $staff = makeStaff($gym);

    $this->actingAs($staff)->get('/settings')->assertForbidden();
    $this->actingAs($staff)->get('/staff')->assertForbidden();
    $this->actingAs($owner)->get('/settings')->assertOk();
    $this->actingAs($owner)->get('/staff')->assertOk();
});

it('lets staff handle members, subscriptions, payments and check-ins', function () {
    [$gym, $owner] = makeOwner();
    $staff = makeStaff($gym);
    $member = Member::factory()->create(['gym_id' => $gym->id]);
    $plan = Plan::factory()->create(['gym_id' => $gym->id]);

    $this->actingAs($staff)->get('/members')->assertOk();
    $this->actingAs($staff)->post('/subscriptions', [
        'member_id' => $member->id, 'plan_id' => $plan->id, 'starts_at' => today()->toDateString(),
    ])->assertRedirect();
    $this->actingAs($staff)->post('/payments', [
        'subscription_id' => 1, 'amount' => 100, 'method' => 'cash',
    ])->assertRedirect();
    $this->actingAs($staff)->post('/check-in', ['member_id' => $member->id])->assertRedirect();
    $this->actingAs($staff)->get('/dashboard')->assertOk();
});

it('redirects guests to login', function () {
    $this->get('/dashboard')->assertRedirect('/login');
    $this->get('/members')->assertRedirect('/login');
});
