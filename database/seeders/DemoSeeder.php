<?php

namespace Database\Seeders;

use App\Models\CheckIn;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Synthetic development data only. Never run against real data.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        abort_unless(app()->environment('local'), 403, 'Demo data is local-only');

        $this->call(RolesAndPermissionsSeeder::class);

        $gym = Gym::create([
            'name' => 'جيم التجمع (بيانات تجريبية)',
            'country' => 'EG',
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'locale' => 'ar',
        ]);

        $owner = User::create([
            'name' => 'مالك تجريبي',
            'email' => 'demo@example.com',
            'password' => Hash::make('password'),
            'gym_id' => $gym->id,
        ]);
        $owner->assignRole(Role::findByName('owner'));

        $monthly = Plan::create(['gym_id' => $gym->id, 'name' => 'شهري', 'type' => 'duration', 'duration_days' => 30, 'price' => 800, 'freeze_days_allowed' => 7]);
        $quarterly = Plan::create(['gym_id' => $gym->id, 'name' => '٣ شهور', 'type' => 'duration', 'duration_days' => 90, 'price' => 2000, 'freeze_days_allowed' => 21]);
        $pack = Plan::create(['gym_id' => $gym->id, 'name' => 'باقة ١٢ حصة', 'type' => 'sessions', 'sessions_count' => 12, 'price' => 1200]);

        $today = Carbon::today();

        $members = collect([
            ['name' => 'أحمد محمود (تجريبي)', 'phone' => '01000000001'],
            ['name' => 'سارة علي (تجريبي)', 'phone' => '01000000002'],
            ['name' => 'محمد حسن (تجريبي)', 'phone' => '01000000003'],
            ['name' => 'منى خالد (تجريبي)', 'phone' => '01000000004'],
        ])->map(fn ($m) => Member::create($m + ['gym_id' => $gym->id]));

        // Active monthly, half paid.
        $s1 = Subscription::create([
            'gym_id' => $gym->id, 'member_id' => $members[0]->id, 'plan_id' => $monthly->id,
            'starts_at' => $today->copy()->subDays(10)->toDateString(),
            'ends_at' => $today->copy()->addDays(19)->toDateString(),
            'status' => 'active', 'price' => $monthly->price,
        ]);
        Payment::create(['gym_id' => $gym->id, 'subscription_id' => $s1->id, 'amount' => 400, 'method' => 'cash', 'paid_at' => now()]);

        // Quarterly, expiring in 5 days, fully paid.
        Subscription::create([
            'gym_id' => $gym->id, 'member_id' => $members[1]->id, 'plan_id' => $quarterly->id,
            'starts_at' => $today->copy()->subDays(85)->toDateString(),
            'ends_at' => $today->copy()->addDays(5)->toDateString(),
            'status' => 'active', 'price' => $quarterly->price,
        ]);

        // Session pack with 9 sessions left.
        $s3 = Subscription::create([
            'gym_id' => $gym->id, 'member_id' => $members[2]->id, 'plan_id' => $pack->id,
            'starts_at' => $today->copy()->subDays(7)->toDateString(),
            'status' => 'active', 'sessions_remaining' => 9, 'price' => $pack->price,
        ]);
        Payment::create(['gym_id' => $gym->id, 'subscription_id' => $s3->id, 'amount' => 1200, 'method' => 'wallet', 'paid_at' => now()]);

        // Expired subscription with no renewal.
        Subscription::create([
            'gym_id' => $gym->id, 'member_id' => $members[3]->id, 'plan_id' => $monthly->id,
            'starts_at' => $today->copy()->subDays(40)->toDateString(),
            'ends_at' => $today->copy()->subDays(11)->toDateString(),
            'status' => 'active', 'price' => $monthly->price,
        ]);

        CheckIn::create(['gym_id' => $gym->id, 'member_id' => $members[0]->id, 'checked_in_at' => now()->subHours(2), 'method' => 'manual', 'result' => 'allowed']);
        CheckIn::create(['gym_id' => $gym->id, 'member_id' => $members[3]->id, 'checked_in_at' => now()->subHour(), 'method' => 'qr', 'result' => 'denied', 'denial_reason' => 'expired']);
    }
}
