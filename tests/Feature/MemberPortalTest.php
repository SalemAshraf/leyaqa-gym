<?php

use App\Models\ClassBooking;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\Hash;

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

it('gives every member a unique private portal token', function () {
    [$gym] = makeOwner();
    $m = Member::factory()->create(['gym_id' => $gym->id]);
    expect($m->portal_token)->not->toBeNull()
        ->and($m->portal_token)->not->toBe($m->qr_token)
        ->and(strlen($m->portal_token))->toBe(64);

    // The stored hash is never a usable link.
    $this->get("/my/{$m->portal_token}")->assertNotFound();
});

it('shows the member their subscription and balance on the private portal', function () {
    [$gym, $member, $sub, $plain] = portalMember();
    \App\Models\Payment::factory()->create(['gym_id' => $gym->id, 'subscription_id' => $sub->id, 'amount' => 200, 'method' => 'cash', 'paid_at' => now()]);
    $sub->update(['price' => 500]);

    $this->get("/my/{$plain}")->assertOk()->assertInertia(fn ($page) => $page
        ->component('MemberPortal')
        ->where('member.name', $member->name)
        ->where('subscription.status', 'active')
        ->where('balance', 300)
        ->has('days')
    );
});

it('keeps the public door card free of money data', function () {
    [, $member] = portalMember();
    $this->get("/card/{$member->qr_token}")->assertOk()->assertInertia(fn ($page) => $page
        ->component('Card')
        ->missing('balance')
    );
    $this->get('/my/'.str()->uuid())->assertNotFound();
});

it('lets a member self-book an active class and cancel it', function () {
    [$gym, $member, , $plain] = portalMember();
    $date = now('Africa/Cairo')->next(Carbon\Carbon::MONDAY)->toDateString();
    $class = GymClass::factory()->create([
        'gym_id' => $gym->id, 'is_active' => true, 'weekday' => Carbon\Carbon::parse($date)->dayOfWeek, 'capacity' => 1,
    ]);

    $this->post("/my/{$plain}/bookings", ['class_id' => $class->id, 'class_date' => $date])
        ->assertSessionHasNoErrors();
    $booking = ClassBooking::withoutGlobalScopes()->first();
    expect($booking->status)->toBe('booked')->and($booking->gym_id)->toBe($gym->id);

    // Dedupe: booking the same class date again is refused.
    $this->post("/my/{$plain}/bookings", ['class_id' => $class->id, 'class_date' => $date])
        ->assertSessionHasErrors('member_id');
    expect(ClassBooking::withoutGlobalScopes()->count())->toBe(1);

    $this->delete("/my/{$plain}/bookings/{$booking->id}")->assertSessionHasNoErrors();
    expect($booking->fresh()->status)->toBe('cancelled');
});

it('waitlists a full class and promotes on cancel, self-service included', function () {
    [$gym, $m1, , $plain1] = portalMember();
    $plan = Plan::first();
    $m2 = Member::factory()->create(['gym_id' => $gym->id, 'status' => 'active']);
    $plain2 = $m2->rotatePortalToken();
    Subscription::factory()->create(['gym_id' => $gym->id, 'member_id' => $m2->id, 'plan_id' => $plan->id, 'status' => 'active', 'starts_at' => today(), 'ends_at' => today()->addMonth()]);

    $date = now('Africa/Cairo')->next(Carbon\Carbon::TUESDAY)->toDateString();
    $class = GymClass::factory()->create(['gym_id' => $gym->id, 'is_active' => true, 'weekday' => Carbon\Carbon::parse($date)->dayOfWeek, 'capacity' => 1]);

    $this->post("/my/{$plain1}/bookings", ['class_id' => $class->id, 'class_date' => $date]);
    $this->post("/my/{$plain2}/bookings", ['class_id' => $class->id, 'class_date' => $date]);
    expect(ClassBooking::withoutGlobalScopes()->where('member_id', $m2->id)->first()->status)->toBe('waitlisted');

    $b1 = ClassBooking::withoutGlobalScopes()->where('member_id', $m1->id)->first();
    $this->delete("/my/{$plain1}/bookings/{$b1->id}");
    expect(ClassBooking::withoutGlobalScopes()->where('member_id', $m2->id)->first()->status)->toBe('booked');
});

it('refuses self-booking without a running subscription', function () {
    [$gym, $member, , $plain] = portalMember(['status' => 'cancelled']);
    $date = now('Africa/Cairo')->next(Carbon\Carbon::WEDNESDAY)->toDateString();
    $class = GymClass::factory()->create(['gym_id' => $gym->id, 'is_active' => true, 'weekday' => Carbon\Carbon::parse($date)->dayOfWeek, 'capacity' => 5]);

    $this->post("/my/{$plain}/bookings", ['class_id' => $class->id, 'class_date' => $date])
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

    $this->delete("/my/{$plain1}/bookings/{$booking->id}")->assertNotFound();
    expect($booking->fresh()->status)->toBe('booked');
});


it('serves the portal with no-store, no-referrer and noindex headers', function () {
    [, $member, , $plain] = portalMember();
    $response = $this->get("/my/{$plain}")->assertOk();
    expect($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and($response->headers->get('Referrer-Policy'))->toBe('no-referrer')
        ->and($response->headers->get('X-Robots-Tag'))->toBe('noindex, nofollow');
});

it('serves an installable manifest for the member portal', function () {
    [, $member, , $plain] = portalMember();
    $json = $this->get("/my/{$plain}/manifest.webmanifest")->assertOk()->json();
    expect($json['start_url'])->toContain("/my/{$plain}")
        ->and($json['display'])->toBe('standalone');
    $this->get('/my/nope/manifest.webmanifest')->assertNotFound();
});

it('lets staff rotate the portal link, revoking the old one immediately', function () {
    [$gym, $member, , $plain, $owner] = portalMember();
    $this->get("/my/{$plain}")->assertOk();

    $this->actingAs($owner)->post(route('members.portal-link', $member->id))
        ->assertSessionHas('portal_link');

    $link = session('portal_link');
    expect($link)->toContain('/my/');

    $this->get("/my/{$plain}")->assertNotFound();
    $this->get($link)->assertOk();
});

it('does not hand staff a stored portal URL on the member page', function () {
    [$gym, $member, , $plain, $owner] = portalMember();
    $this->actingAs($owner)->get(route('members.show', $member->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->missing('member.portal_url'));
});
