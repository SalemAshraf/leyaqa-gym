<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

function passwordFile(string $password = 'correct horse battery'): string
{
    $path = tempnam(sys_get_temp_dir(), 'pw');
    file_put_contents($path, $password);
    chmod($path, 0600);

    return $path;
}

it('creates a verified gym-less platform admin who can enter the platform area', function () {
    $this->artisan('platform:admin', [
        'email' => 'Boss@Example.com',
        '--name' => 'المدير',
        '--password-file' => passwordFile(),
    ])->assertSuccessful();

    $admin = User::where('email', 'boss@example.com')->first();
    expect($admin)->not->toBeNull()
        ->and($admin->is_platform_admin)->toBeTrue()
        ->and($admin->gym_id)->toBeNull()
        ->and($admin->hasVerifiedEmail())->toBeTrue()
        ->and($admin->isPlatformAdmin())->toBeTrue();

    $this->actingAs($admin)->get('/platform')->assertOk();
});

it('lands a gym-less platform admin on the platform panel after login', function () {
    $this->artisan('platform:admin', [
        'email' => 'boss@example.com',
        '--name' => 'المدير',
        '--password-file' => passwordFile('secret-phrase-123'),
    ])->assertSuccessful();

    $this->post('/login', ['email' => 'boss@example.com', 'password' => 'secret-phrase-123'])
        ->assertRedirect(route('platform.index', absolute: false));
});

it('refuses to elevate a tenant account', function () {
    [$gym, $owner] = makeOwner();

    $this->artisan('platform:admin', ['email' => $owner->email])->assertFailed();

    expect($owner->fresh()->is_platform_admin)->toBeFalse();
    $this->actingAs($owner->fresh())->get('/platform')->assertForbidden();
});

it('grants an existing gym-less login and revokes it cleanly', function () {
    $user = User::factory()->create(['gym_id' => null]);

    $this->artisan('platform:admin', ['email' => $user->email])->assertSuccessful();
    expect($user->fresh()->isPlatformAdmin())->toBeTrue();
    $this->actingAs($user->fresh())->get('/platform')->assertOk();

    Auth::guard('web')->forgetUser();
    $this->artisan('platform:admin', ['email' => $user->email, '--revoke' => true])->assertSuccessful();
    expect($user->fresh()->is_platform_admin)->toBeFalse();
    $this->actingAs($user->fresh())->get('/platform')->assertForbidden();
});

it('keeps the env-listed emails working as a fallback', function () {
    config()->set('platform.admin_emails', ['legacy@example.com']);
    $user = User::factory()->create(['gym_id' => null, 'email' => 'legacy@example.com']);

    expect($user->refresh()->is_platform_admin)->toBeFalse()
        ->and($user->isPlatformAdmin())->toBeTrue();
    $this->actingAs($user)->get('/platform')->assertOk();
});

it('rejects short passwords and missing names for new logins', function () {
    $this->artisan('platform:admin', [
        'email' => 'a@example.com', '--name' => 'X', '--password-file' => passwordFile('short'),
    ])->assertFailed();
    $this->artisan('platform:admin', [
        'email' => 'a@example.com', '--password-file' => passwordFile(),
    ])->assertFailed();

    expect(User::where('email', 'a@example.com')->exists())->toBeFalse();
});
