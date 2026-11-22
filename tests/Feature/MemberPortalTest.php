<?php

use App\Models\ClassBooking;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;

function portalMember(array $subOver = []): array
{
    [$gym, $owner] = makeOwner();
    $gym->update(['timezone' => 'Africa/Cairo']);
    $plan = Plan::factory()->create(['gym_id' => $gym->id]);
    $member = Member::factory()->create(['gym_id' => $gym->id, 'status' => 'active']);
    $sub = Subscription::factory()->create(array_merge([
        'gym_id' => $gym->id, 'member_id' => $member->id, 'plan_id' => $plan->id,
        'status' => 'active', 'starts_at' => today(), 'ends_at' => today()->addMonth(),
    ], $subOver));
    $plain = $member->rotatePortalToken();

    return [$gym, $member, $sub, $plain, $owner];
}

function portalExchange($t, string $plain): void
{
    $t->get("/my/{$plain}")->assertRedirect(route('portal.show'));
}

it('stores only a hash and never serves the stored value as a link', function () {
    [$gym] = makeOwner();
    $m = Member::factory()->create(['gym_id' => $gym->id]);
    expect($m->portal_token)->not->toBeNull()
        ->and($m->portal_token)->not->toBe($m->qr_token)
        ->and(strlen($m->portal_token))->toBe(64);

    $this->get("/my/{$m->portal_token}")->assertRedirect(route('portal.expired'));
});

it('shows the member their subscription and balance on the private portal', function () {
    [$gym, $member, $sub, $plain] = portalMember();
    \App\Models\Payment::factory()->create(['gym_id' => $gym->id, 'subscription_id' => $sub->id, 'amount' => 200, 'method' => 'cash', 'paid_at' => now()]);
    $sub->update(['price' => 500]);

    portalExchange($this, $plain);
    $this->get('/my')->assertOk()->assertInertia(fn ($page) => $page
        ->component('MemberPortal')
        ->where('member.name', $member->name)
        ->where('subscription.status', 'active')
        ->where('balance', 300)
        ->has('days')
        ->missing('token')
    );
});

it('makes every bootstrap link single-use', function () {
    [, $member, , $plain] = portalMember();

    portalExchange($this, $plain);
    $this->get('/my')->assertOk();

    // A fresh device replaying the same link is refused.
    $this->flushSession();
    $this->get("/my/{$plain}")->assertRedirect(route('portal.expired'));

    // The original device still has its session.
    $this->get('/my')->assertRedirect(route('portal.expired')); // flushed here too
});

it('keeps the original device session after the link is consumed', function () {
    [, $member, , $plain] = portalMember();

    portalExchange($this, $plain);
    $this->get('/my')->assertOk();

    // Re-tapping the old link on the SAME device rides the live session.
    $this->get("/my/{$plain}")->assertRedirect(route('portal.show'));
    $this->get('/my')->assertOk();
});

it('keeps the public door card free of money data', function () {
    [, $member] = portalMember();
    $this->get("/card/{$member->qr_token}")->assertOk()->assertInertia(fn ($page) => $page
        ->component('Card')
        ->missing('balance')
    );
    $this->get('/my/'.str()->uuid())->assertRedirect(route('portal.expired'));
});

it('lets a member self-book an active class and cancel it', function () {
    [$gym, $member, , $plain] = portalMember();
    $date = now('Africa/Cairo')->next(Carbon\Carbon::MONDAY)->toDateString();
    $class = GymClass::factory()->create([
        'gym_id' => $gym->id, 'is_active' => true, 'weekday' => Carbon\Carbon::parse($date)->dayOfWeek, 'capacity' => 1,
    ]);

    portalExchange($this, $plain);
    $this->post('/my/bookings', ['class_id' => $class->id, 'class_date' => $date])
        ->assertSessionHasNoErrors();
    $booking = ClassBooking::withoutGlobalScopes()->first();
    expect($booking->status)->toBe('booked')->and($booking->gym_id)->toBe($gym->id);

    // Dedupe: booking the same class date again is refused.
    $this->post('/my/bookings', ['class_id' => $class->id, 'class_date' => $date])
        ->assertSessionHasErrors('member_id');
    expect(ClassBooking::withoutGlobalScopes()->count())->toBe(1);

    $this->delete("/my/bookings/{$booking->id}")->assertSessionHasNoErrors();
    expect($booking->fresh()->status)->toBe('cancelled');
});

it('waitlists a full class and promotes on cancel, self-service included', function () {
    [$gym, $m1, , $plain1] = portalMember();
    $plan = Plan::first();
    $m2 = Member::factory()->create(['gym_id' => $gym->id, 'status' => 'active']);
    Subscription::factory()->create(['gym_id' => $gym->id, 'member_id' => $m2->id, 'plan_id' => $plan->id, 'status' => 'active', 'starts_at' => today(), 'ends_at' => today()->addMonth()]);
    $plain2 = $m2->rotatePortalToken();

    $date = now('Africa/Cairo')->next(Carbon\Carbon::TUESDAY)->toDateString();
    $class = GymClass::factory()->create(['gym_id' => $gym->id, 'is_active' => true, 'weekday' => Carbon\Carbon::parse($date)->dayOfWeek, 'capacity' => 1]);

    portalExchange($this, $plain1);
    $this->post('/my/bookings', ['class_id' => $class->id, 'class_date' => $date]);
    portalExchange($this, $plain2);
    $this->post('/my/bookings', ['class_id' => $class->id, 'class_date' => $date]);
    expect(ClassBooking::withoutGlobalScopes()->where('member_id', $m2->id)->first()->status)->toBe('waitlisted');

    // m1 cancels: needs a fresh link (rotation revokes old sessions).
    $plain1b = $m1->rotatePortalToken();
    portalExchange($this, $plain1b);
    $b1 = ClassBooking::withoutGlobalScopes()->where('member_id', $m1->id)->first();
    $this->delete("/my/bookings/{$b1->id}");
    expect(ClassBooking::withoutGlobalScopes()->where('member_id', $m2->id)->first()->status)->toBe('booked');
});

it('refuses self-booking without a running subscription', function () {
    [$gym, $member, , $plain] = portalMember(['status' => 'cancelled']);
    $date = now('Africa/Cairo')->next(Carbon\Carbon::WEDNESDAY)->toDateString();
    $class = GymClass::factory()->create(['gym_id' => $gym->id, 'is_active' => true, 'weekday' => Carbon\Carbon::parse($date)->dayOfWeek, 'capacity' => 5]);

    portalExchange($this, $plain);
    $this->post('/my/bookings', ['class_id' => $class->id, 'class_date' => $date])
        ->assertSessionHasErrors('member_id');
    expect(ClassBooking::withoutGlobalScopes()->count())->toBe(0);
});

it('never lets a member cancel another member\'s booking', function () {
    [$gym, $m1, , $plain1] = portalMember();
    $m2 = Member::factory()->create(['gym_id' => $gym->id]);
    $date = now('Africa/Cairo')->next(Carbon\Carbon::THURSDAY)->toDateString();
    $class = GymClass::factory()->create(['gym_id' => $gym->id, 'is_active' => true, 'weekday' => Carbon\Carbon::parse($date)->dayOfWeek, 'capacity' => 5]);
    $booking = ClassBooking::withoutGlobalScopes()->create([
        'gym_id' => $gym->id, 'gym_class_id' => $class->id, 'member_id' => $m2->id, 'class_date' => $date, 'status' => 'booked',
    ]);

    portalExchange($this, $plain1);
    $this->delete("/my/bookings/{$booking->id}")->assertNotFound();
    expect($booking->fresh()->status)->toBe('booked');
});

it('serves the portal with no-store, no-referrer and noindex headers', function () {
    [, $member, , $plain] = portalMember();
    portalExchange($this, $plain);
    $response = $this->get('/my')->assertOk();
    expect($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and($response->headers->get('Referrer-Policy'))->toBe('no-referrer')
        ->and($response->headers->get('X-Robots-Tag'))->toBe('noindex, nofollow');
});

it('serves an installable manifest for the member portal', function () {
    [, $member, , $plain] = portalMember();
    portalExchange($this, $plain);
    $json = $this->get('/my/manifest.webmanifest')->assertOk()->json();
    expect($json['start_url'])->toContain('/my')
        ->and($json['start_url'])->not->toContain($plain)
        ->and($json['display'])->toBe('standalone');

    $this->flushSession();
    $this->get('/my/manifest.webmanifest')->assertRedirect(route('portal.expired'));
});

it('lets staff rotate the portal link, revoking the old one and live sessions', function () {
    [$gym, $member, , $plain, $owner] = portalMember();
    portalExchange($this, $plain);
    $this->get('/my')->assertOk();

    $this->actingAs($owner)->post(route('members.portal-link', $member->id))
        ->assertSessionHas('portal_link');

    $link = session('portal_link');
    expect($link)->toContain('/my/');

    // Old bootstrap dead, old web session revoked.
    $this->flushSession();
    $this->get("/my/{$plain}")->assertRedirect(route('portal.expired'));

    // New link works.
    $this->get($link)->assertRedirect(route('portal.show'));
    $this->get('/my')->assertOk();
});

it('does not hand staff a stored portal URL on the member page', function () {
    [$gym, $member, , $plain, $owner] = portalMember();
    $this->actingAs($owner)->get(route('members.show', $member->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->missing('member.portal_url'));
});

it('expires portal links after the gym-configured lifetime', function () {
    [$gym, $member, , $plain] = portalMember();

    // Default 90 days: a link issued 91 days ago is invalid.
    $member->forceFill(['portal_token_issued_at' => now()->subDays(91)])->save();
    $this->get("/my/{$plain}")->assertRedirect(route('portal.expired'));

    // 0 disables expiry: the same link works again.
    $gym->update(['portal_link_days' => 0]);
    $this->get("/my/{$plain}")->assertRedirect(route('portal.show'));
});

it('lets the owner set the portal link lifetime from settings', function () {
    [$gym, $member, , $plain, $owner] = portalMember();
    $this->actingAs($owner)->patch(route('settings.portal-link'), ['portal_link_days' => 30])
        ->assertSessionHasNoErrors();
    expect($gym->fresh()->portal_link_days)->toBe(30);

    $this->actingAs($owner)->patch(route('settings.portal-link'), ['portal_link_days' => 400])
        ->assertSessionHasErrors('portal_link_days');
});
