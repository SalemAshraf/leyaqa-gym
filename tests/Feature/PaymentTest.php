<?php

use App\Models\Member;
use App\Models\Payment;
use App\Models\Subscription;

it('tracks partial payments and outstanding balance', function () {
    [$gym, $owner] = makeOwner();
    $sub = Subscription::factory()->create(['gym_id' => $gym->id, 'price' => 800]);

    $this->actingAs($owner)->post('/payments', ['subscription_id' => $sub->id, 'amount' => 300, 'method' => 'cash']);
    $this->actingAs($owner)->post('/payments', ['subscription_id' => $sub->id, 'amount' => 200, 'method' => 'wallet']);

    expect($sub->refresh()->paidAmount())->toBe(500.0)
        ->and($sub->balance())->toBe(300.0)
        ->and($sub->member->fresh()->outstandingBalance())->toBe(300.0);
});

it('only lets the owner delete a payment', function () {
    [$gym, $owner] = makeOwner();
    $staff = makeStaff($gym);
    $payment = Payment::factory()->create(['gym_id' => $gym->id]);

    $this->actingAs($staff)->delete("/payments/{$payment->id}")->assertForbidden();
    expect(Payment::find($payment->id))->not->toBeNull();

    $this->actingAs($owner)->delete("/payments/{$payment->id}")->assertRedirect();
    expect(Payment::find($payment->id))->toBeNull();
});

it('rejects invalid payment methods and zero amounts', function () {
    [$gym, $owner] = makeOwner();
    $sub = Subscription::factory()->create(['gym_id' => $gym->id]);

    $this->actingAs($owner)->post('/payments', [
        'subscription_id' => $sub->id, 'amount' => 0, 'method' => 'crypto',
    ])->assertSessionHasErrors(['amount', 'method']);
});
