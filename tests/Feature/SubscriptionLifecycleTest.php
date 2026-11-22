<?php

use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;

it('creates a duration subscription with inclusive end date', function () {
    [$gym, $owner] = makeOwner();
    $member = Member::factory()->create(['gym_id' => $gym->id]);
    $plan = Plan::factory()->create(['gym_id' => $gym->id, 'duration_days' => 30, 'price' => 800]);

    $this->actingAs($owner)->post('/subscriptions', [
        'member_id' => $member->id, 'plan_id' => $plan->id, 'starts_at' => '2026-12-01',
    ])->assertRedirect();

    $sub = Subscription::first();
    expect($sub->starts_at->toDateString())->toBe('2026-12-01')
        ->and($sub->ends_at->toDateString())->toBe('2026-12-30')
        ->and($sub->price)->toBe('800.00')
        ->and($sub->status)->toBe('active');
});

it('creates a session-pack subscription with session balance', function () {
    [$gym, $owner] = makeOwner();
    $member = Member::factory()->create(['gym_id' => $gym->id]);
    $plan = Plan::factory()->sessions(12)->create(['gym_id' => $gym->id]);

    $this->actingAs($owner)->post('/subscriptions', [
        'member_id' => $member->id, 'plan_id' => $plan->id, 'starts_at' => today()->toDateString(),
    ]);

    $sub = Subscription::first();
    expect($sub->ends_at)->toBeNull()->and($sub->sessions_remaining)->toBe(12);
});

it('freezes within allowance and extends the end date', function () {
    [$gym, $owner] = makeOwner();
    $member = Member::factory()->create(['gym_id' => $gym->id]);
    $plan = Plan::factory()->create(['gym_id' => $gym->id, 'freeze_days_allowed' => 10]);
    $sub = Subscription::factory()->create([
        'gym_id' => $gym->id, 'member_id' => $member->id, 'plan_id' => $plan->id,
        'starts_at' => today()->subDays(5)->toDateString(),
        'ends_at' => today()->addDays(24)->toDateString(),
    ]);

    $this->actingAs($owner)->post("/subscriptions/{$sub->id}/freeze", ['days' => 6])->assertRedirect();

    $sub->refresh();
    expect($sub->status)->toBe('frozen')
        ->and($sub->freeze_days_used)->toBe(6)
        ->and($sub->ends_at->toDateString())->toBe(today()->addDays(30)->toDateString());
});

it('refuses freeze beyond the plan allowance', function () {
    [$gym, $owner] = makeOwner();
    $member = Member::factory()->create(['gym_id' => $gym->id]);
    $plan = Plan::factory()->create(['gym_id' => $gym->id, 'freeze_days_allowed' => 5]);
    $sub = Subscription::factory()->create([
        'gym_id' => $gym->id, 'member_id' => $member->id, 'plan_id' => $plan->id,
        'starts_at' => today()->toDateString(), 'ends_at' => today()->addDays(29)->toDateString(),
    ]);

    $this->actingAs($owner)->post("/subscriptions/{$sub->id}/freeze", ['days' => 6])->assertStatus(422);
    expect($sub->refresh()->status)->toBe('active');
});

it('unfreezing early re-credits unused days and pulls the end date back', function () {
    [$gym, $owner] = makeOwner();
    $member = Member::factory()->create(['gym_id' => $gym->id]);
    $plan = Plan::factory()->create(['gym_id' => $gym->id, 'freeze_days_allowed' => 10]);
    $sub = Subscription::factory()->create([
        'gym_id' => $gym->id, 'member_id' => $member->id, 'plan_id' => $plan->id,
        'starts_at' => today()->subDays(5)->toDateString(),
        'ends_at' => today()->addDays(24)->toDateString(),
    ]);

    Carbon::setTestNow(today());
    $this->actingAs($owner)->post("/subscriptions/{$sub->id}/freeze", ['days' => 8]);

    Carbon::setTestNow(today()->addDays(3));
    $this->actingAs($owner)->post("/subscriptions/{$sub->id}/unfreeze");

    $sub->refresh();
    expect($sub->status)->toBe('active')
        ->and($sub->freeze_days_used)->toBe(3)
        ->and($sub->ends_at->toDateString())->toBe(today()->addDays(24)->toDateString());
    Carbon::setTestNow();
});

it('derives expired status from the end date even if the row still says active', function () {
    [$gym, $owner] = makeOwner();
    $member = Member::factory()->create(['gym_id' => $gym->id]);
    $sub = Subscription::factory()->create([
        'gym_id' => $gym->id, 'member_id' => $member->id,
        'starts_at' => today()->subDays(40)->toDateString(),
        'ends_at' => today()->subDays(11)->toDateString(),
        'status' => 'active',
    ]);

    expect($sub->effectiveStatus())->toBe('expired');
    [$allowed, $reason] = $sub->canEnter();
    expect($allowed)->toBeFalse()->and($reason)->toBe('expired');
});

it('renews starting the day after the current end when still running', function () {
    [$gym, $owner] = makeOwner();
    $member = Member::factory()->create(['gym_id' => $gym->id]);
    $plan = Plan::factory()->create(['gym_id' => $gym->id, 'duration_days' => 30]);
    $sub = Subscription::factory()->create([
        'gym_id' => $gym->id, 'member_id' => $member->id, 'plan_id' => $plan->id,
        'starts_at' => today()->subDays(10)->toDateString(),
        'ends_at' => today()->addDays(19)->toDateString(),
    ]);

    $this->actingAs($owner)->post("/subscriptions/{$sub->id}/renew")->assertRedirect();

    $new = Subscription::whereKeyNot($sub->id)->first();
    expect($new->starts_at->toDateString())->toBe(today()->addDays(20)->toDateString())
        ->and($new->ends_at->toDateString())->toBe(today()->addDays(49)->toDateString());
});

it('cancels and blocks payment on cancelled subscriptions', function () {
    [$gym, $owner] = makeOwner();
    $sub = Subscription::factory()->create(['gym_id' => $gym->id]);

    $this->actingAs($owner)->post("/subscriptions/{$sub->id}/cancel")->assertRedirect();
    expect($sub->refresh()->status)->toBe('cancelled');

    $this->actingAs($owner)->post('/payments', [
        'subscription_id' => $sub->id, 'amount' => 100, 'method' => 'cash',
    ])->assertStatus(422);
});
