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

    return [$gym, $member, $sub];
}

it('gives every member a unique private portal token', function () {
    [$gym] = makeOwner();
    $m = Member::factory()->create(['gym_id' => $gym->id]);
    expect($m->portal_token)->not->toBeNull()
        ->and($m->portal_token)->not->toBe($m->qr_token);
});

it('shows the member their subscription and balance on the private portal', function () {
    [$gym, $member, $sub] = portalMember();
    \App\Models\Payment::factory()->create(['gym_id' => $gym->id, 'subscription_id' => $sub->id, 'amount' => 200, 'method' => 'cash', 'paid_at' => now()]);
    $sub->update(['price' => 500]);

    $this->get("/my/{$member->portal_token}")->assertOk()->assertInertia(fn ($page) => $page
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
    [$gym, $member] = portalMember();
    $date = now('Africa/Cairo')->next(Carbon\Carbon::MONDAY)->toDateString();
    $class = GymClass::factory()->create([
        'gym_id' => $gym->id, 'is_active' => true, 'weekday' => Carbon\Carbon::parse($date)->dayOfWeek, 'capacity' => 1,
    ]);

    $this->post("/my/{$member->portal_token}/bookings", ['class_id' => $class->id, 'class_date' => $date])
        ->assertSessionHasNoErrors();
    $booking = ClassBooking::withoutGlobalScopes()->first();
    expect($booking->status)->toBe('booked')->and($booking->gym_id)->toBe($gym->id);

    // Dedupe: booking the same class date again is refused.
    $this->post("/my/{$member->portal_token}/bookings", ['class_id' => $class->id, 'class_date' => $date])
        ->assertSessionHasErrors('member_id');
    expect(ClassBooking::withoutGlobalScopes()->count())->toBe(1);

    $this->delete("/my/{$member->portal_token}/bookings/{$booking->id}")->assertSessionHasNoErrors();
    expect($booking->fresh()->status)->toBe('cancelled');
});

it('waitlists a full class and promotes on cancel, self-service included', function () {
    [$gym, $m1] = portalMember();
    $plan = Plan::first();
    $m2 = Member::factory()->create(['gym_id' => $gym->id, 'status' => 'active']);
    Subscription::factory()->create(['gym_id' => $gym->id, 'member_id' => $m2->id, 'plan_id' => $plan->id, 'status' => 'active', 'starts_at' => today(), 'ends_at' => today()->addMonth()]);

    $date = now('Africa/Cairo')->next(Carbon\Carbon::TUESDAY)->toDateString();
    $class = GymClass::factory()->create(['gym_id' => $gym->id, 'is_active' => true, 'weekday' => Carbon\Carbon::parse($date)->dayOfWeek, 'capacity' => 1]);

    $this->post("/my/{$m1->portal_token}/bookings", ['class_id' => $class->id, 'class_date' => $date]);
    $this->post("/my/{$m2->portal_token}/bookings", ['class_id' => $class->id, 'class_date' => $date]);
    expect(ClassBooking::withoutGlobalScopes()->where('member_id', $m2->id)->first()->status)->toBe('waitlisted');

    $b1 = ClassBooking::withoutGlobalScopes()->where('member_id', $m1->id)->first();
    $this->delete("/my/{$m1->portal_token}/bookings/{$b1->id}");
    expect(ClassBooking::withoutGlobalScopes()->where('member_id', $m2->id)->first()->status)->toBe('booked');
});

it('refuses self-booking without a running subscription', function () {
    [$gym, $member] = portalMember(['status' => 'cancelled']);
    $date = now('Africa/Cairo')->next(Carbon\Carbon::WEDNESDAY)->toDateString();
    $class = GymClass::factory()->create(['gym_id' => $gym->id, 'is_active' => true, 'weekday' => Carbon\Carbon::parse($date)->dayOfWeek, 'capacity' => 5]);

    $this->post("/my/{$member->portal_token}/bookings", ['class_id' => $class->id, 'class_date' => $date])
        ->assertSessionHasErrors('member_id');
    expect(ClassBooking::withoutGlobalScopes()->count())->toBe(0);
});

it('never lets a member cancel another member\'s booking', function () {
    [$gym, $m1] = portalMember();
    $m2 = Member::factory()->create(['gym_id' => $gym->id]);
    $date = now('Africa/Cairo')->next(Carbon\Carbon::THURSDAY)->toDateString();
    $class = GymClass::factory()->create(['gym_id' => $gym->id, 'is_active' => true, 'weekday' => Carbon\Carbon::parse($date)->dayOfWeek, 'capacity' => 5]);
    $booking = ClassBooking::withoutGlobalScopes()->create([
        'gym_id' => $gym->id, 'gym_class_id' => $class->id, 'member_id' => $m2->id, 'class_date' => $date, 'status' => 'booked',
    ]);

    $this->delete("/my/{$m1->portal_token}/bookings/{$booking->id}")->assertNotFound();
    expect($booking->fresh()->status)->toBe('booked');
});
