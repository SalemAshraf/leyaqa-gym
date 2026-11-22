<?php

use App\Models\Member;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\RenewalInvoice;
use App\Models\Subscription;

function renewalSetup(array $subOver = []): array
{
    [$gym, $owner] = makeOwner();
    $gym->update(['renewal_invoices_enabled' => true]);
    $plan = Plan::factory()->create(['gym_id' => $gym->id, 'price' => 500]);
    $member = Member::factory()->create(['gym_id' => $gym->id]);
    $sub = Subscription::factory()->create(array_merge([
        'gym_id' => $gym->id, 'member_id' => $member->id, 'plan_id' => $plan->id,
        'status' => 'active', 'price' => 500,
        'starts_at' => today()->subMonth(), 'ends_at' => today()->addDays(3),
    ], $subOver));

    return [$gym, $owner, $member, $sub];
}

it('generates due invoices for ending cycles and dedupes on re-run', function () {
    [$gym, $owner, $member, $sub] = renewalSetup();
    // Not ending soon: untouched.
    $far = Subscription::factory()->create([
        'gym_id' => $gym->id, 'member_id' => $member->id, 'plan_id' => $sub->plan_id,
        'status' => 'active', 'price' => 700, 'starts_at' => today(), 'ends_at' => today()->addMonth(),
    ]);

    $this->artisan('gym:process-renewals')->assertSuccessful();
    $this->artisan('gym:process-renewals')->assertSuccessful();

    $invoices = RenewalInvoice::withoutGlobalScopes()->get();
    expect($invoices)->toHaveCount(1)
        ->and($invoices->first()->status)->toBe('due')
        ->and((float) $invoices->first()->amount)->toBe(500.0)
        ->and($invoices->first()->due_date->toDateString())->toBe($sub->ends_at->toDateString())
        ->and($invoices->first()->gym_id)->toBe($gym->id);
});

it('skips free and cancelled subscriptions', function () {
    [$gym, , $member, $sub] = renewalSetup();
    $sub->update(['status' => 'cancelled']);
    $free = Subscription::factory()->create([
        'gym_id' => $gym->id, 'member_id' => $member->id, 'plan_id' => $sub->plan_id,
        'status' => 'active', 'price' => 0, 'starts_at' => today()->subMonth(), 'ends_at' => today()->addDays(2),
    ]);

    $this->artisan('gym:process-renewals')->assertSuccessful();
    expect(RenewalInvoice::withoutGlobalScopes()->count())->toBe(0);
});

it('collects a due invoice into a normal payment and never auto-charges', function () {
    [$gym, $owner, $member, $sub] = renewalSetup();
    $this->artisan('gym:process-renewals')->assertSuccessful();
    $invoice = RenewalInvoice::withoutGlobalScopes()->firstOrFail();

    $this->actingAs($owner)->post(route('renewal-invoices.collect', $invoice->id), [
        'amount' => 450, 'method' => 'wallet',
    ])->assertSessionHasNoErrors();

    $payment = Payment::withoutGlobalScopes()->firstOrFail();
    $invoice = $invoice->fresh();
    expect((float) $payment->amount)->toBe(450.0)
        ->and($payment->method)->toBe('wallet')
        ->and($payment->renewal_invoice_id)->toBe($invoice->id)
        ->and($payment->subscription_id)->toBe($sub->id)
        ->and($payment->gym_id)->toBe($gym->id)
        ->and($invoice->status)->toBe('collected')
        ->and((float) $invoice->paid_amount)->toBe(450.0)
        ->and($invoice->paid_method)->toBe('wallet');

    // A collected invoice cannot be collected or cancelled again.
    $this->actingAs($owner)->post(route('renewal-invoices.collect', $invoice->id), [
        'amount' => 50, 'method' => 'cash',
    ])->assertStatus(422);
    $this->actingAs($owner)->post(route('renewal-invoices.cancel', $invoice->id))->assertStatus(422);
    expect(Payment::withoutGlobalScopes()->count())->toBe(1);
});

it('cancels a due invoice without recording money', function () {
    [$gym, $owner] = renewalSetup();
    $this->artisan('gym:process-renewals')->assertSuccessful();
    $invoice = RenewalInvoice::withoutGlobalScopes()->firstOrFail();

    $this->actingAs($owner)->post(route('renewal-invoices.cancel', $invoice->id))
        ->assertSessionHasNoErrors();
    $invoice = $invoice->fresh();
    expect($invoice->status)->toBe('cancelled')
        ->and($invoice->cancelled_at)->not->toBeNull()
        ->and($invoice->cancelled_by)->toBe($owner->id)
        ->and(Payment::withoutGlobalScopes()->count())->toBe(0);
});

it('keeps renewal invoices inside their gym', function () {
    [$gym, $owner] = renewalSetup();
    $this->artisan('gym:process-renewals')->assertSuccessful();

    [$gym2, $owner2] = makeOwner();
    $invoice = RenewalInvoice::withoutGlobalScopes()->firstOrFail();

    $this->actingAs($owner2)->get(route('renewal-invoices.index'))
        ->assertOk()->assertInertia(fn ($page) => $page->has('invoices.data', 0));
    $this->actingAs($owner2)->post(route('renewal-invoices.collect', $invoice->id))->assertNotFound();
});

it('creates nothing for gyms that have not opted in', function () {
    [$gym, $owner] = makeOwner();
    $plan = \App\Models\Plan::factory()->create(['gym_id' => $gym->id]);
    $member = \App\Models\Member::factory()->create(['gym_id' => $gym->id]);
    \App\Models\Subscription::factory()->create([
        'gym_id' => $gym->id, 'member_id' => $member->id, 'plan_id' => $plan->id,
        'status' => 'active', 'price' => 500, 'starts_at' => today()->subMonth(), 'ends_at' => today()->addDays(3),
    ]);

    $this->artisan('gym:process-renewals')->assertSuccessful();
    expect(RenewalInvoice::withoutGlobalScopes()->count())->toBe(0);
});

it('moves the cycle invoice and its payment to the renewed subscription', function () {
    [$gym, $owner, $member, $sub] = renewalSetup();
    $this->artisan('gym:process-renewals')->assertSuccessful();
    $invoice = RenewalInvoice::withoutGlobalScopes()->firstOrFail();
    $this->actingAs($owner)->post(route('renewal-invoices.collect', $invoice->id), [
        'amount' => 500, 'method' => 'cash',
    ])->assertSessionHasNoErrors();

    $this->actingAs($owner)->post(route('subscriptions.renew', $sub->id), [])
        ->assertSessionHasNoErrors();

    $newSub = Subscription::withoutGlobalScopes()->where('member_id', $member->id)->latest('id')->firstOrFail();
    expect($newSub->id)->not->toBe($sub->id)
        ->and($invoice->fresh()->subscription_id)->toBe($newSub->id)
        ->and(Payment::withoutGlobalScopes()->firstOrFail()->subscription_id)->toBe($newSub->id);

    // The settled cycle cannot be collected again through the normal flow.
    $this->actingAs($owner)->post(route('payments.store'), [
        'subscription_id' => $newSub->id, 'amount' => 100, 'method' => 'cash',
    ])->assertStatus(422);
    expect(Payment::withoutGlobalScopes()->count())->toBe(1);
});
