<?php

use App\Models\CheckIn;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;

function gymWithMember(array $planState = [], array $subState = []): array
{
    [$gym, $owner] = makeOwner();
    $member = Member::factory()->create(['gym_id' => $gym->id]);
    $plan = Plan::factory()->create(['gym_id' => $gym->id] + $planState);
    $sub = Subscription::factory()->create([
        'gym_id' => $gym->id, 'member_id' => $member->id, 'plan_id' => $plan->id,
    ] + $subState);

    return [$gym, $owner, $member, $plan, $sub];
}

it('allows an active member and records the check-in', function () {
    [$gym, $owner, $member] = gymWithMember();

    $this->actingAs($owner)->post('/check-in', ['member_id' => $member->id])->assertRedirect();

    $checkIn = CheckIn::first();
    expect($checkIn->result)->toBe('allowed')->and($checkIn->method)->toBe('manual');
});

it('finds members by qr token', function () {
    [$gym, $owner, $member] = gymWithMember();

    $this->actingAs($owner)->post('/check-in', ['qr_token' => $member->qr_token])->assertRedirect();

    expect(CheckIn::first()->method)->toBe('qr');
});

it('denies expired subscriptions', function () {
    [$gym, $owner, $member] = gymWithMember([], [
        'starts_at' => today()->subDays(40)->toDateString(),
        'ends_at' => today()->subDays(11)->toDateString(),
    ]);

    $this->actingAs($owner)->post('/check-in', ['member_id' => $member->id]);

    $checkIn = CheckIn::first();
    expect($checkIn->result)->toBe('denied')->and($checkIn->denial_reason)->toBe('expired');
});

it('denies members with no subscription at all', function () {
    [$gym, $owner] = makeOwner();
    $member = Member::factory()->create(['gym_id' => $gym->id]);

    $this->actingAs($owner)->post('/check-in', ['member_id' => $member->id]);

    expect(CheckIn::first()->denial_reason)->toBe('no_subscription');
});

it('decrements session packs on entry and denies when empty', function () {
    [$gym, $owner, $member, $plan, $sub] = gymWithMember(
        ['type' => 'sessions', 'duration_days' => null, 'sessions_count' => 2],
        ['ends_at' => null, 'sessions_remaining' => 2],
    );

    $this->actingAs($owner)->post('/check-in', ['member_id' => $member->id]);
    $this->actingAs($owner)->post('/check-in', ['member_id' => $member->id]);
    expect($sub->refresh()->sessions_remaining)->toBe(0);

    $this->actingAs($owner)->post('/check-in', ['member_id' => $member->id]);
    expect(CheckIn::latest('id')->first()->denial_reason)->toBe('no_sessions_left');
});

it('denies frozen subscriptions', function () {
    [$gym, $owner, $member, $plan, $sub] = gymWithMember([], ['status' => 'frozen']);

    $this->actingAs($owner)->post('/check-in', ['member_id' => $member->id]);

    expect(CheckIn::first()->denial_reason)->toBe('frozen');
});

it('lets a member with unpaid balance in, and reports the debt', function () {
    [$gym, $owner, $member, $plan, $sub] = gymWithMember([], ['price' => 800]);

    $this->actingAs($owner)->post('/check-in', ['member_id' => $member->id]);

    expect(CheckIn::first()->result)->toBe('allowed')
        ->and(session('checkin')['balance'])->toBe(800.0);
});
