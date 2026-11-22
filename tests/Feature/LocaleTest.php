<?php

use App\Models\Member;

it('persists the locale on the user and shares it with inertia', function () {
    [$gym, $owner] = makeOwner();
    expect($owner->locale)->toBeNull();

    $this->actingAs($owner)->post('/locale', ['locale' => 'en'])->assertRedirect();
    expect($owner->fresh()->locale)->toBe('en');

    $this->actingAs($owner)->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('locale', 'en'));
});

it('keeps the locale in session for guests and falls back to the gym locale', function () {
    [$gym, $owner] = makeOwner();
    $gym->update(['locale' => 'en']);

    $this->post('/locale', ['locale' => 'en'])->assertRedirect();
    $this->get('/login')->assertInertia(fn ($page) => $page->where('locale', 'en'));

    // No user preference and no session: the gym default wins.
    $this->flushSession();
    $this->actingAs($owner)->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('locale', 'en'));
});

it('rejects unsupported locales', function () {
    [$gym, $owner] = makeOwner();

    $this->actingAs($owner)->post('/locale', ['locale' => 'fr'])->assertSessionHasErrors('locale');
    expect($owner->fresh()->locale)->toBeNull();
});
