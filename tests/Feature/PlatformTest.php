<?php

use App\Models\Gym;
use App\Models\PlatformInvoice;
use App\Models\PlatformPlan;

it('starts every new gym on a free trial once terms are configured', function () {
    config()->set('platform.trial_days', 14);

    $this->post('/register', [
        'gym_name' => 'جيم التجربة', 'country' => 'EG', 'name' => 'Owner',
        'email' => 'trial@example.com', 'password' => 'password', 'password_confirmation' => 'password',
    ]);

    $gym = Gym::where('name', 'جيم التجربة')->first();
    expect($gym->platform_status)->toBe('trialing')
        ->and($gym->trial_ends_at->toDateString())->toBe(now('Africa/Cairo')->addDays(14)->toDateString())
        ->and($gym->trialDaysLeft())->toBeGreaterThan(10)
        ->and($gym->platformAccessible())->toBeTrue();
});

it('locks the app to the billing page when the trial expires', function () {
    [$gym, $owner] = makeOwner();
    $gym->update(['platform_status' => 'trialing', 'trial_ends_at' => now('Africa/Cairo')->subDay()]);

    $this->actingAs($owner)->get('/dashboard')->assertRedirect(route('billing.show', absolute: false));
    $this->actingAs($owner)->get('/members')->assertRedirect(route('billing.show', absolute: false));
    // Billing itself stays reachable so the owner can resubscribe.
    $this->actingAs($owner)->get('/billing')->assertOk();
});

it('lets the owner request a plan and the platform admin confirm payment', function () {
    config()->set('platform.admin_emails', ['boss@quwa.app']);
    [$gym, $owner] = makeOwner();
    $gym->update(['platform_status' => 'trialing', 'trial_ends_at' => now('Africa/Cairo')->subDay()]);
    $plan = PlatformPlan::where('slug', 'monthly')->first();

    // Owner requests the plan: a pending invoice appears, one at a time.
    $this->actingAs($owner)->post('/billing/subscribe', ['plan_id' => $plan->id])->assertSessionHasNoErrors();
    $this->actingAs($owner)->post('/billing/subscribe', ['plan_id' => $plan->id])->assertSessionHasNoErrors();
    expect(PlatformInvoice::where('gym_id', $gym->id)->where('status', 'pending')->count())->toBe(1);
    $invoice = PlatformInvoice::first();
    expect($invoice->period_ends_at->toDateString())->toBe(now('Africa/Cairo')->addMonth()->toDateString());

    // A non-admin owner is refused from the platform area.
    $this->actingAs($owner)->get('/platform')->assertForbidden();
    $this->actingAs($owner)->post("/platform/invoices/{$invoice->id}/paid")->assertForbidden();

    // The platform admin marks it paid: gym activates and the app unlocks.
    [$adminGym, $admin] = makeOwner();
    $admin->update(['email' => 'boss@quwa.app']);
    $this->actingAs($admin)->post("/platform/invoices/{$invoice->id}/paid", ['amount_cents' => 49900])->assertSessionHasNoErrors();

    $gym->refresh();
    expect($gym->platform_status)->toBe('active')
        ->and($gym->platform_plan_id)->toBe($plan->id)
        ->and($gym->platform_renews_at->toDateString())->toBe($invoice->period_ends_at->toDateString())
        ->and($gym->platformAccessible())->toBeTrue();
    // Re-authenticate with a fresh instance: actingAs caches the gym relation.
    $this->actingAs($owner->fresh())->get('/dashboard')->assertOk();
});

it('extends trials and suspends gyms as platform admin', function () {
    config()->set('platform.admin_emails', ['boss@quwa.app']);
    [$gym, $owner] = makeOwner();
    $gym->update(['platform_status' => 'trialing', 'trial_ends_at' => now('Africa/Cairo')->subDays(3)]);

    [$adminGym, $admin] = makeOwner();
    $admin->update(['email' => 'boss@quwa.app']);

    $this->actingAs($admin)->post("/platform/gyms/{$gym->id}/extend-trial", ['days' => 10]);
    $gym->refresh();
    expect($gym->platform_status)->toBe('trialing')
        ->and($gym->trial_ends_at->toDateString())->toBe(now('Africa/Cairo')->addDays(10)->toDateString())
        ->and($gym->platformAccessible())->toBeTrue();

    $this->actingAs($admin)->post("/platform/gyms/{$gym->id}/status", ['status' => 'suspended']);
    expect($gym->fresh()->platformAccessible())->toBeFalse();
    $this->actingAs($owner->fresh())->get('/dashboard')->assertRedirect(route('billing.show', absolute: false));
});

it('keeps active gyms inside the grace period accessible', function () {
    config()->set('platform.grace_days', 7);
    [$gym, $owner] = makeOwner();
    $gym->update(['platform_status' => 'active', 'platform_renews_at' => now('Africa/Cairo')->subDays(3)]);
    expect($gym->platformAccessible())->toBeTrue();

    $gym->update(['platform_renews_at' => now('Africa/Cairo')->subDays(30)]);
    expect($gym->platformAccessible())->toBeFalse();
});

it('never confirms a priceless invoice without an explicit amount', function () {
    config()->set('platform.admin_emails', ['boss@quwa.app']);
    [$gym, $owner] = makeOwner();
    $gym->update(['platform_status' => 'pending']);
    $plan = PlatformPlan::where('slug', 'monthly')->first(); // price NULL by default
    $this->actingAs($owner)->post('/billing/subscribe', ['plan_id' => $plan->id]);
    $invoice = PlatformInvoice::first();
    expect($invoice->amount_cents)->toBeNull();

    [$aG, $admin] = makeOwner();
    $admin->update(['email' => 'boss@quwa.app']);

    // Confirming without an amount is rejected; nothing activates.
    $this->actingAs($admin)->post("/platform/invoices/{$invoice->id}/paid")->assertSessionHasErrors('amount_cents');
    expect($invoice->fresh()->status)->toBe('pending');
    expect($gym->fresh()->platform_status)->not->toBe('active');

    // The collected amount is recorded explicitly with the confirmation.
    $this->actingAs($admin)->post("/platform/invoices/{$invoice->id}/paid", ['amount_cents' => 49900]);
    $invoice->refresh();
    expect($invoice->status)->toBe('paid')->and($invoice->amount_cents)->toBe(49900);
    expect($gym->fresh()->platform_status)->toBe('active');
});

it('does not let an unverified email claim platform admin access', function () {
    config()->set('platform.admin_emails', ['boss@quwa.app']);
    [$gym, $owner] = makeOwner();

    // Tenant user sets their profile email to the admin address: the change unverifies it.
    $owner->forceFill(['email' => 'boss@quwa.app', 'email_verified_at' => null])->save();
    $this->actingAs($owner->fresh())->get('/platform')->assertForbidden();

    // Only a verified owner of that address gets in.
    $owner->forceFill(['email_verified_at' => now()])->save();
    $this->actingAs($owner->fresh())->get('/platform')->assertOk();
});

it('registers gyms as pending until the platform owner activates them', function () {
    config()->set('platform.trial_days', null);

    $this->post('/register', [
        'gym_name' => 'جيم منتظر', 'country' => 'EG', 'name' => 'Owner',
        'email' => 'pending@example.com', 'password' => 'password', 'password_confirmation' => 'password',
    ]);

    $gym = Gym::where('name', 'جيم منتظر')->first();
    expect($gym->platform_status)->toBe('pending')->and($gym->platformAccessible())->toBeFalse();

    // The new owner lands on the billing page, which stays reachable.
    $owner = $gym->users()->first();
    $this->actingAs($owner->fresh())->get('/dashboard')->assertRedirect(route('billing.show', absolute: false));
    $this->actingAs($owner->fresh())->get('/billing')->assertOk();
});

it('gives no grace period until one is configured', function () {
    config()->set('platform.grace_days', 0);
    [$gym, $owner] = makeOwner();
    $gym->update(['platform_status' => 'active', 'platform_renews_at' => now('Africa/Cairo')->subDay()]);
    expect($gym->platformAccessible())->toBeFalse();
});
