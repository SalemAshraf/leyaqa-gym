<?php

use App\Models\Member;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;

it('hides other gyms members from index and direct access', function () {
    [$gymA, $ownerA] = makeOwner();
    [$gymB, $ownerB] = makeOwner();

    $memberA = Member::factory()->create(['gym_id' => $gymA->id, 'name' => 'Member A']);
    $memberB = Member::factory()->create(['gym_id' => $gymB->id, 'name' => 'Member B']);

    $this->actingAs($ownerA)->get('/members')->assertOk()->assertDontSee('Member B');
    $this->actingAs($ownerA)->get("/members/{$memberB->id}")->assertNotFound();
    $this->actingAs($ownerA)->put("/members/{$memberB->id}", [
        'name' => 'Hacked', 'phone' => '0100', 'status' => 'active',
    ])->assertNotFound();
});

it('scopes plans, subscriptions and payments to the gym', function () {
    [$gymA, $ownerA] = makeOwner();
    [$gymB, $ownerB] = makeOwner();

    $planB = Plan::factory()->create(['gym_id' => $gymB->id, 'name' => 'Plan B']);
    $memberB = Member::factory()->create(['gym_id' => $gymB->id]);
    $subB = Subscription::factory()->create(['gym_id' => $gymB->id, 'member_id' => $memberB->id, 'plan_id' => $planB->id]);
    $paymentB = Payment::factory()->create(['gym_id' => $gymB->id, 'subscription_id' => $subB->id]);

    // Owner A cannot attach a subscription to gym B's member or plan (scope makes findOrFail 404).
    $this->actingAs($ownerA)->post('/subscriptions', [
        'member_id' => $memberB->id, 'plan_id' => $planB->id, 'starts_at' => today()->toDateString(),
    ])->assertNotFound();

    $this->actingAs($ownerA)->post("/subscriptions/{$subB->id}/cancel")->assertNotFound();
    $this->actingAs($ownerA)->delete("/payments/{$paymentB->id}")->assertNotFound();
});

it('never leaks another gyms member through qr token check-in', function () {
    [$gymA, $ownerA] = makeOwner();
    [$gymB, $ownerB] = makeOwner();
    $memberB = Member::factory()->create(['gym_id' => $gymB->id]);

    $this->actingAs($ownerA)->post('/check-in', ['qr_token' => $memberB->qr_token])->assertNotFound();
});
