<?php

use App\Models\Member;

it('finds members regardless of arabic letter variants in the booking search', function () {
    [$gym, $owner] = makeOwner();
    $target = Member::factory()->create(['gym_id' => $gym->id, 'name' => 'أحمد سامي', 'status' => 'active']);
    Member::factory()->create(['gym_id' => $gym->id, 'name' => 'خالد عمر', 'status' => 'active']);

    $res = $this->actingAs($owner)->getJson('/members/search?q=' . urlencode('احمد'));
    $res->assertOk();
    expect(collect($res->json())->pluck('id'))->toContain($target->id);

    $res = $this->actingAs($owner)->getJson('/members/search?q=' . urlencode('سامى'));
    expect(collect($res->json())->pluck('id'))->toContain($target->id);
});

it('applies the same tolerant search on the members index', function () {
    [$gym, $owner] = makeOwner();
    Member::factory()->create(['gym_id' => $gym->id, 'name' => 'إبراهيم فتحي', 'status' => 'active']);

    $this->actingAs($owner)->get('/members?search=' . urlencode('ابراهيم'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('members.data', 1));
});
