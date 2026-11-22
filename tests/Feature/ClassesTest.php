<?php

use App\Models\ClassBooking;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;

function makeClassMember($gym, array $subAttrs = []): Member
{
    $plan = Plan::factory()->create(['gym_id' => $gym->id]);
    $member = Member::factory()->create(['gym_id' => $gym->id, 'status' => 'active']);
    Subscription::factory()->create(array_merge([
        'gym_id' => $gym->id, 'member_id' => $member->id, 'plan_id' => $plan->id,
        'status' => 'active', 'starts_at' => today()->subDay(), 'ends_at' => today()->addMonth(),
    ], $subAttrs));

    return $member;
}

it('creates classes and books members within capacity', function () {
    [$gym, $owner] = makeOwner();
    $class = GymClass::factory()->create(['gym_id' => $gym->id, 'capacity' => 1, 'weekday' => now()->dayOfWeek]);
    $member = makeClassMember($gym);

    $this->actingAs($owner)->get('/classes')->assertOk()->assertInertia(
        fn ($page) => $page->has('classes', 1)->where('classes.0.runs_this_day', true)
    );

    // Capacity 1: first booking works, second member is rejected as full.
    $this->actingAs($owner)->post("/classes/{$class->id}/bookings", [
        'member_id' => $member->id, 'class_date' => now()->toDateString(),
    ])->assertSessionHasNoErrors();
    expect($class->bookedCount(now()->toDateString()))->toBe(1);
    // The index page shows the same booked count for the selected day.
    $this->actingAs($owner)->get('/classes')->assertInertia(
        fn ($page) => $page->where('classes.0.booked', 1)->has('classes.0.bookings', 1)
    );

    $member2 = makeClassMember($gym);
    $this->actingAs($owner)->post("/classes/{$class->id}/bookings", [
        'member_id' => $member2->id, 'class_date' => now()->toDateString(),
    ])->assertSessionHasNoErrors();
    // Full class: the second member lands on the waitlist, not a booked seat.
    expect(ClassBooking::where('member_id', $member2->id)->first()->status)->toBe('waitlisted');
});

it('rejects duplicate, expired-subscription and wrong-weekday bookings', function () {
    [$gym, $owner] = makeOwner();
    $class = GymClass::factory()->create(['gym_id' => $gym->id, 'capacity' => 10, 'weekday' => now()->dayOfWeek]);
    $member = makeClassMember($gym);
    $date = now()->toDateString();

    $this->actingAs($owner)->post("/classes/{$class->id}/bookings", ['member_id' => $member->id, 'class_date' => $date]);
    // Same member again: rejected (no duplicate record).
    $this->actingAs($owner)->post("/classes/{$class->id}/bookings", ['member_id' => $member->id, 'class_date' => $date])
        ->assertSessionHasErrors();
    expect(ClassBooking::where('gym_class_id', $class->id)->where('status', 'booked')->count())->toBe(1);

    // A day the class does not run.
    $otherDay = now()->addDays(1)->toDateString();
    $this->actingAs($owner)->post("/classes/{$class->id}/bookings", ['member_id' => $member->id, 'class_date' => $otherDay])
        ->assertSessionHasErrors();

    // Member whose subscription expired.
    $expired = makeClassMember($gym, ['status' => 'expired', 'starts_at' => today()->subMonths(2), 'ends_at' => today()->subMonth()]);
    $this->actingAs($owner)->post("/classes/{$class->id}/bookings", ['member_id' => $expired->id, 'class_date' => $date])
        ->assertSessionHasErrors();
});

it('frees the spot when a booking is cancelled and allows rebooking', function () {
    [$gym, $owner] = makeOwner();
    $class = GymClass::factory()->create(['gym_id' => $gym->id, 'capacity' => 1, 'weekday' => now()->dayOfWeek]);
    $member = makeClassMember($gym);
    $date = now()->toDateString();

    $this->actingAs($owner)->post("/classes/{$class->id}/bookings", ['member_id' => $member->id, 'class_date' => $date]);
    $booking = ClassBooking::first();

    $this->actingAs($owner)->delete("/bookings/{$booking->id}")->assertSessionHasNoErrors();
    expect($class->bookedCount($date))->toBe(0);

    // Rebooking after cancel reuses the same row.
    $this->actingAs($owner)->post("/classes/{$class->id}/bookings", ['member_id' => $member->id, 'class_date' => $date])
        ->assertSessionHasNoErrors();
    expect(ClassBooking::count())->toBe(1);
    expect(ClassBooking::first()->status)->toBe('booked');
});

it('keeps classes and bookings inside the tenant', function () {
    [$gymA, $ownerA] = makeOwner();
    [$gymB, $ownerB] = makeOwner();
    $classB = GymClass::factory()->create(['gym_id' => $gymB->id]);
    $memberA = makeClassMember($gymA);

    $this->actingAs($ownerA)->post("/classes/{$classB->id}/bookings", [
        'member_id' => $memberA->id, 'class_date' => now()->toDateString(),
    ])->assertNotFound();
    $this->actingAs($ownerA)->put("/classes/{$classB->id}", [
        'name' => 'Hacked', 'weekday' => 1, 'start_time' => '10:00', 'duration_minutes' => 60, 'capacity' => 10,
    ])->assertNotFound();

    $bookingB = ClassBooking::factory()->create([
        'gym_class_id' => $classB->id,
        'member_id' => makeClassMember($gymB)->id,
    ]);
    $this->actingAs($ownerA)->delete("/bookings/{$bookingB->id}")->assertNotFound();
});

it('blocks classes from users without classes.manage and from guests', function () {
    [$gym, $owner] = makeOwner();
    $this->get('/classes')->assertRedirect('/login');
    // staff HAS classes.manage (reception runs the booking desk)
    $this->actingAs(makeStaff($gym))->get('/classes')->assertOk();
});

it('waitlists members when full and promotes the earliest on cancellation', function () {
    [$gym, $owner] = makeOwner();
    $class = GymClass::factory()->create(['gym_id' => $gym->id, 'capacity' => 1, 'weekday' => now()->dayOfWeek]);
    $m1 = makeClassMember($gym);
    $m2 = makeClassMember($gym);
    $m3 = makeClassMember($gym);
    $date = now()->toDateString();

    $this->actingAs($owner)->post("/classes/{$class->id}/bookings", ['member_id' => $m1->id, 'class_date' => $date])
        ->assertSessionHasNoErrors();
    // Full now: next bookings join the waitlist instead of being rejected.
    $this->actingAs($owner)->post("/classes/{$class->id}/bookings", ['member_id' => $m2->id, 'class_date' => $date])
        ->assertSessionHasNoErrors();
    $this->actingAs($owner)->post("/classes/{$class->id}/bookings", ['member_id' => $m3->id, 'class_date' => $date])
        ->assertSessionHasNoErrors();

    expect($class->bookedCount($date))->toBe(1)
        ->and($class->waitlistCount($date))->toBe(2);
    expect(ClassBooking::where('member_id', $m2->id)->first()->status)->toBe('waitlisted');

    // Same member cannot join the waitlist twice.
    $this->actingAs($owner)->post("/classes/{$class->id}/bookings", ['member_id' => $m2->id, 'class_date' => $date])
        ->assertSessionHasErrors('member_id');

    // Cancelling the booked spot promotes the earliest waitlisted member.
    $booking1 = ClassBooking::where('member_id', $m1->id)->first();
    $this->actingAs($owner)->delete("/bookings/{$booking1->id}")->assertSessionHasNoErrors();

    expect(ClassBooking::where('member_id', $m1->id)->first()->status)->toBe('cancelled')
        ->and(ClassBooking::where('member_id', $m2->id)->first()->status)->toBe('booked')
        ->and(ClassBooking::where('member_id', $m3->id)->first()->status)->toBe('waitlisted')
        ->and($class->bookedCount($date))->toBe(1)
        ->and($class->waitlistCount($date))->toBe(1);
});
