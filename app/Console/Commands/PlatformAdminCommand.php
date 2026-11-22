<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Grants or revokes access to the platform admin area (/platform).
 *
 * Platform admins are their own identity: a user with no gym, never a tenant
 * account elevated in place. The flag lives on the user row, so no personal
 * address is hardcoded into the repository or config. Passwords come from a
 * 0600 file or a hidden prompt, never from CLI arguments, and this command
 * sends no email or notification of any kind.
 */
class PlatformAdminCommand extends Command
{
    protected $signature = 'platform:admin
        {email : Login email of the platform admin}
        {--name= : Display name (required when creating a new login)}
        {--password-file= : Path to a 0600 file holding the password; hidden prompt when omitted}
        {--revoke : Remove platform access from this email instead of granting it}';

    protected $description = 'Create (or revoke) a platform admin login for the /platform sales/admin panel';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address.');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($this->option('revoke')) {
            return $this->revoke($user, $email);
        }

        return $user ? $this->grantExisting($user) : $this->createNew($email);
    }

    private function revoke(?User $user, string $email): int
    {
        if (! $user || ! $user->is_platform_admin) {
            $this->warn("No DB-granted platform admin found for {$email}; nothing revoked.");

            return self::FAILURE;
        }

        $user->forceFill(['is_platform_admin' => false])->save();
        $this->info("Platform access revoked for {$email}.");

        if (in_array($email, config('platform.admin_emails', []), true)) {
            $this->warn('This email is also listed in PLATFORM_ADMIN_EMAILS; remove it there too for a full revoke.');
        }

        return self::SUCCESS;
    }

    private function grantExisting(User $user): int
    {
        // Tenant separation: never elevate an account that belongs to a gym.
        if ($user->gym_id !== null) {
            $this->error("{$user->email} belongs to a gym (tenant account). Refusing to elevate it; use a dedicated platform address.");

            return self::FAILURE;
        }

        $updates = ['is_platform_admin' => true];
        if ($user->email_verified_at === null) {
            $updates['email_verified_at'] = now();
        }
        if ($path = $this->option('password-file')) {
            $updates['password'] = Hash::make($this->readPasswordFile($path));
        }

        $user->forceFill($updates)->save();
        $this->info("Platform access granted to existing login {$user->email}.");

        return self::SUCCESS;
    }

    private function createNew(string $email): int
    {
        $name = trim((string) ($this->option('name') ?? ''));
        if ($name === '') {
            $this->error('A --name is required when creating a new platform admin.');

            return self::FAILURE;
        }

        if ($path = $this->option('password-file')) {
            $password = $this->readPasswordFile($path);
        } else {
            $password = $this->secret('Password for the new platform admin (min 12 chars)');
            $confirmation = $this->secret('Confirm password');
            if ($password !== $confirmation) {
                $this->error('Passwords do not match.');

                return self::FAILURE;
            }
        }

        if (mb_strlen((string) $password) < 12) {
            $this->error('Password must be at least 12 characters.');

            return self::FAILURE;
        }

        // forceFill: is_platform_admin and email_verified_at are intentionally
        // outside $fillable so no mass-assignment path can grant platform access.
        (new User())->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'gym_id' => null,
            'email_verified_at' => now(),
            'is_platform_admin' => true,
        ])->save();

        $this->info("Platform admin {$email} created. Sign in at /login, then open /platform.");

        return self::SUCCESS;
    }

    private function readPasswordFile(string $path): string
    {
        if (! is_readable($path)) {
            $this->error("Password file not readable: {$path}");
            exit(self::FAILURE);
        }

        return rtrim((string) file_get_contents($path), "\r\n");
    }
}
