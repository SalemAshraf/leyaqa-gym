<?php

use App\Models\ClassBooking;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;

function apiMember(): array
{
    [$gym, $owner] = makeOwner();
    $gym->update(['timezone' => 'Africa/Cairo']);
    $plan = Plan::factory()->create(['gym_id' => $gym->id]);
    $member = Member::factory()->create(['gym_id' => $gym->id, 'status' => 'active']);
    Subscription::factory()->create([
        'gym_id' => $gym->id, 'member_id' => $member->id, 'plan_id' => $plan->id,
        'status' => 'active', 'starts_at' => today(), 'ends_at' => today()->addMonth(),
    ]);

    return [$gym, $member];
}

function asMember($t, Member $m): array
{
    // Exchange a single-use bootstrap link for a bounded API session token.
    $bootstrap = $m->rotatePortalToken();
    $token = $t->postJson('/api/member/session', ['token' => $bootstrap])
        ->assertCreated()
        ->json('token');

    return ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
}

it('rejects missing and wrong tokens with 401', function () {
    $this->getJson('/api/member/profile')->assertUnauthorized();
    $this->getJson('/api/member/profile', ['Authorization' => 'Bearer nope'])->assertUnauthorized();
    $this->postJson('/api/member/session', ['token' => 'nope'])->assertUnauthorized();
});

it('consumes the bootstrap token at exchange', function () {
    [, $member] = apiMember();
    $bootstrap = $member->rotatePortalToken();
    $this->postJson('/api/member/session', ['token' => $bootstrap])->assertCreated();
    // Replay is refused.
    $this->postJson('/api/member/session', ['token' => $bootstrap])->assertUnauthorized();
});

it('rejects a revoked session token after staff rotation', function () {
    [, $member] = apiMember();
    $headers = asMember($this, $member);
    $this->getJson('/api/member/profile', $headers)->assertOk();

    $member->rotatePortalToken(); // revokes live sessions
    $this->getJson('/api/member/profile', $headers)->assertUnauthorized();
});

it('serves profile, subscription and balance as JSON', function () {
    [$gym, $member] = apiMember();

    $this->getJson('/api/member/profile', asMember($this, $member))
        ->assertOk()
        ->assertJsonPath('member.name', $member->name)
        ->assertJsonPath('subscription.status', 'active')
        ->assertJsonPath('gym.name', $gym->name)
        ->assertJsonStructure(['balance', 'qr_token']);
});

it('lists bookable classes and books and cancels via the API', function () {
    [$gym, $member] = apiMember();
    $date = now('Africa/Cairo')->next(Carbon\Carbon::FRIDAY)->toDateString();
    $class = GymClass::factory()->create([
        'gym_id' => $gym->id, 'is_active' => true, 'weekday' => Carbon\Carbon::parse($date)->dayOfWeek, 'capacity' => 3,
    ]);

    $days = $this->getJson('/api/member/classes', asMember($this, $member))->assertOk()->json('days');
    expect(collect($days)->firstWhere('date', $date)['classes'][0]['id'])->toBe($class->id);

    $created = $this->postJson('/api/member/bookings', ['class_id' => $class->id, 'class_date' => $date], asMember($this, $member))
        ->assertCreated()
        ->json('booking');
    expect($created['status'])->toBe('booked');

    $this->getJson('/api/member/bookings', asMember($this, $member))
        ->assertOk()
        ->assertJsonPath('bookings.0.id', $created['id']);

    $this->deleteJson("/api/member/bookings/{$created['id']}", [], asMember($this, $member))
        ->assertOk()
        ->assertJsonPath('cancelled', true);
});

it('returns 422 with a message when the member has no running subscription', function () {
    [$gym, $member] = apiMember();
    Subscription::withoutGlobalScopes()->where('member_id', $member->id)->update(['status' => 'cancelled']);
    $date = now('Africa/Cairo')->next(Carbon\Carbon::SATURDAY)->toDateString();
    $class = GymClass::factory()->create([
        'gym_id' => $gym->id, 'is_active' => true, 'weekday' => Carbon\Carbon::parse($date)->dayOfWeek, 'capacity' => 3,
    ]);

    $this->postJson('/api/member/bookings', ['class_id' => $class->id, 'class_date' => $date], asMember($this, $member))
        ->assertUnprocessable();
});

it('never exposes or mutates another member\'s bookings', function () {
    [$gym, $member] = apiMember();
    $other = Member::factory()->create(['gym_id' => $gym->id]);
    $date = now('Africa/Cairo')->next(Carbon\Carbon::SUNDAY)->toDateString();
    $class = GymClass::factory()->create(['gym_id' => $gym->id, 'is_active' => true, 'weekday' => Carbon\Carbon::parse($date)->dayOfWeek, 'capacity' => 5]);
    $booking = ClassBooking::withoutGlobalScopes()->create([
        'gym_id' => $gym->id, 'gym_class_id' => $class->id, 'member_id' => $other->id, 'class_date' => $date, 'status' => 'booked',
    ]);

    $this->deleteJson("/api/member/bookings/{$booking->id}", [], asMember($this, $member))->assertNotFound();
    expect($booking->fresh()->status)->toBe('booked');
});

it('serves member API responses with no-store headers', function () {
    [, $member] = apiMember();
    $this->getJson('/api/member/profile', asMember($this, $member))
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private');
});
