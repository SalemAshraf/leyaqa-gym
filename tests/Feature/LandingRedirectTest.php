<?php

use App\Models\User;

it('routes a platform admin to the platform dashboard from home', function () {
    $admin = User::factory()->create(['is_platform_admin' => true, 'gym_id' => null]);
    $this->actingAs($admin)->get('/')->assertRedirect(route('platform.index'));
});

it('keeps tenant owners on the tenant dashboard from home', function () {
    [$gym, $owner] = makeOwner();
    $this->actingAs($owner)->get('/')->assertRedirect(route('dashboard'));
});
