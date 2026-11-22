<?php

test('registration screen can be rendered', function () {
    $this->get('/register')->assertOk();
});

test('new users can register with their gym', function () {
    $response = $this->post('/register', [
        'gym_name' => 'Test Gym',
        'country' => 'EG',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});
