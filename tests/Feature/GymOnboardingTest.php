<?php

use App\Models\Gym;
use App\Models\User;

it('registers a gym with an owner in one step', function () {
    $response = $this->post('/register', [
        'gym_name' => 'جيم النصر',
        'country' => 'SA',
        'name' => 'Salem Owner',
        'email' => 'owner@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));

    $gym = Gym::first();
    expect($gym->name)->toBe('جيم النصر')
        ->and($gym->currency)->toBe('SAR')
        ->and($gym->timezone)->toBe('Asia/Riyadh');

    $user = User::first();
    expect($user->gym_id)->toBe($gym->id)
        ->and($user->hasRole('owner'))->toBeTrue()
        ->and($user->can('plans.manage'))->toBeTrue()
        ->and($user->can('staff.manage'))->toBeTrue();
});

it('requires gym name and country', function () {
    $this->post('/register', [
        'name' => 'X', 'email' => 'x@example.com',
        'password' => 'password', 'password_confirmation' => 'password',
    ])->assertSessionHasErrors(['gym_name', 'country']);
});
