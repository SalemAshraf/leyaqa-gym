<?php

use App\Models\Gym;
use App\Models\Member;
use App\Models\Plan;
use App\Models\ReminderLog;
use App\Models\Subscription;

function reminderGym(array $gymOver = [], array $subOver = []): array
{
    [$gym, $owner] = makeOwner();
    $gym->update(array_merge([
        'timezone' => 'Africa/Cairo', 'locale' => 'ar', 'currency' => 'EGP',
        'reminders_enabled' => true, 'reminder_days_before' => 3,
    ], $gymOver));
    $plan = Plan::factory()->create(['gym_id' => $gym->id]);
    $member = Member::factory()->create(['gym_id' => $gym->id, 'status' => 'active', 'phone' => '01000000001']);
    $sub = Subscription::factory()->create(array_merge([
        'gym_id' => $gym->id, 'member_id' => $member->id, 'plan_id' => $plan->id,
        'status' => 'active', 'starts_at' => today()->subWeek(), 'ends_at' => today()->addDays(3),
    ], $subOver));

    return [$gym, $member, $sub];
}

// Current Cairo hour, so the command's local-hour gate passes.
function cairoHour(): string
{
    return (string) now('Africa/Cairo')->hour;
}

it('sends expiry reminders only for opted-in gyms and logs them once', function () {
    [$gym, $member] = reminderGym();

    $this->artisan('gym:send-reminders', ['--hour' => cairoHour()])->assertSuccessful();
    expect(ReminderLog::where('member_id', $member->id)->where('kind', 'expiry')->count())->toBe(1);

    // Second run: dedupe by (member, kind, reference_date).
    $this->artisan('gym:send-reminders', ['--hour' => cairoHour()])->assertSuccessful();
    expect(ReminderLog::where('member_id', $member->id)->where('kind', 'expiry')->count())->toBe(1);

    $log = ReminderLog::where('member_id', $member->id)->first();
    expect($log->channel)->toBe('log')
        ->and($log->body)->toContain($member->name)->toContain($gym->name);
});

it('sends nothing for gyms that did not opt in', function () {
    reminderGym(['reminders_enabled' => false]);

    $this->artisan('gym:send-reminders', ['--hour' => cairoHour()])->assertSuccessful();
    expect(ReminderLog::count())->toBe(0);
});

it('respects the gym-local send hour', function () {
    reminderGym();
    $otherHour = (string) ((now('Africa/Cairo')->hour + 5) % 24);

    $this->artisan('gym:send-reminders', ['--hour' => $otherHour])->assertSuccessful();
    expect(ReminderLog::count())->toBe(0);
});

it('reminds members with debt at most once a week', function () {
    [$gym, $member, $sub] = reminderGym([], ['ends_at' => today()->addMonth()]);
    \App\Models\Payment::factory()->create(['gym_id' => $gym->id, 'subscription_id' => $sub->id, 'amount' => 100, 'method' => 'cash', 'paid_at' => now()]);
    $sub->update(['price' => 400]);

    $this->artisan('gym:send-reminders', ['--hour' => cairoHour()])->assertSuccessful();
    $this->artisan('gym:send-reminders', ['--hour' => cairoHour()])->assertSuccessful();
    expect(ReminderLog::where('member_id', $member->id)->where('kind', 'debt')->count())->toBe(1);
});

it('skips members without a phone', function () {
    [$gym, $member] = reminderGym();
    $member->update(['phone' => '']);

    $this->artisan('gym:send-reminders', ['--hour' => cairoHour()])->assertSuccessful();
    expect(ReminderLog::count())->toBe(0);
});

it('lets the owner toggle reminders from settings', function () {
    [$gym, $owner] = makeOwner();

    $this->actingAs($owner)->patch('/settings/reminders', ['enabled' => true, 'days_before' => 5])
        ->assertSessionHasNoErrors();
    expect($gym->fresh()->reminders_enabled)->toBeTrue()
        ->and($gym->fresh()->reminder_days_before)->toBe(5);

    $this->actingAs($owner)->patch('/settings/reminders', ['enabled' => true, 'days_before' => 99])
        ->assertSessionHasErrors('days_before');
});
