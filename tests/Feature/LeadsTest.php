<?php

use App\Models\Lead;
use App\Models\Member;

it('creates, filters and updates leads', function () {
    [$gym, $owner] = makeOwner();

    $this->actingAs($owner)->post('/leads', [
        'name' => 'كريم سيد', 'phone' => '01000000001', 'source' => 'انستجرام',
    ])->assertSessionHasNoErrors();
    expect(Lead::count())->toBe(1);
    $lead = Lead::first();
    expect($lead->status)->toBe('new')->and($lead->created_by)->toBe($owner->id);

    $this->actingAs($owner)->get('/leads?status=new')->assertInertia(fn ($page) => $page->has('leads.data', 1));
    $this->actingAs($owner)->get('/leads?status=lost')->assertInertia(fn ($page) => $page->has('leads.data', 0));
    $this->actingAs($owner)->get('/leads?search=كريم')->assertInertia(fn ($page) => $page->has('leads.data', 1));

    $this->actingAs($owner)->put("/leads/{$lead->id}", [
        'name' => 'كريم سيد', 'phone' => '01000000001', 'source' => 'انستجرام', 'notes' => 'مهتم بباقة ٣ شهور', 'status' => 'contacted',
    ])->assertSessionHasNoErrors();
    expect($lead->fresh()->status)->toBe('contacted');
});

it('converts a lead to a member once, idempotently', function () {
    [$gym, $owner] = makeOwner();
    $lead = Lead::factory()->for($gym)->create(['name' => 'منى عادل', 'phone' => '01000000002']);

    $this->actingAs($owner)->post("/leads/{$lead->id}/convert")->assertSessionHasNoErrors();
    $lead->refresh();
    expect($lead->status)->toBe('converted');
    $member = Member::first();
    expect($member->name)->toBe('منى عادل')->and($member->phone)->toBe('01000000002')->and($member->status)->toBe('active');
    expect($lead->member_id)->toBe($member->id);

    // Converting again returns the same member, no duplicate.
    $this->actingAs($owner)->post("/leads/{$lead->id}/convert")->assertSessionHasNoErrors();
    expect(Member::count())->toBe(1);
    expect($lead->fresh()->member_id)->toBe($member->id);

    // Converted leads cannot be deleted or un-converted through update.
    $this->actingAs($owner)->delete("/leads/{$lead->id}")->assertStatus(422);
    $this->actingAs($owner)->put("/leads/{$lead->id}", [
        'name' => 'منى عادل', 'phone' => '01000000002', 'status' => 'new',
    ]);
    expect($lead->fresh()->status)->toBe('converted');
});

it('keeps leads inside the tenant and behind members.manage', function () {
    [$gymA, $ownerA] = makeOwner();
    [$gymB, $ownerB] = makeOwner();
    $leadB = Lead::factory()->for($gymB)->create();

    $this->actingAs($ownerA)->put("/leads/{$leadB->id}", [
        'name' => 'Hacked', 'phone' => '01000000009', 'status' => 'lost',
    ])->assertNotFound();
    $this->actingAs($ownerA)->post("/leads/{$leadB->id}/convert")->assertNotFound();
    $this->actingAs($ownerA)->delete("/leads/{$leadB->id}")->assertNotFound();
    expect($leadB->fresh()->name)->not->toBe('Hacked');

    $staff = makeStaff($gymA); // staff has members.manage (reception works leads)
    $this->actingAs($staff)->get('/leads')->assertOk();
    \Illuminate\Support\Facades\Auth::logout();
    $this->get('/leads')->assertRedirect('/login');
});
