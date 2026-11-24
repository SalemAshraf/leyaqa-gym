<?php

namespace App\Console\Commands;

use App\Models\CheckIn;
use App\Models\ClassBooking;
use App\Models\Gym;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Permissions;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Creates ONE isolated demo gym with synthetic data for evaluation.
 * Additive only: refuses to run when the demo accounts already exist,
 * and never touches any other gym or user. All names, phones and
 * payments are fictional.
 */
class CreateDemoGym extends Command
{
    protected $signature = 'gym:demo {--force : Required when APP_ENV=production} {--password-file= : Path to a 0600 file holding the demo password}';

    protected $description = 'Create an isolated demo gym with synthetic data and demo logins (additive, refuses to overwrite)';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Refusing in production without --force. This creates a synthetic demo gym only.');

            return self::FAILURE;
        }

        if (User::withoutGlobalScopes()->where('email', 'demo@example.com')->exists()) {
            $this->error('Demo accounts already exist (demo@example.com). Refusing to touch existing records.');

            return self::FAILURE;
        }

        $password = null;
        if ($file = $this->option('password-file')) {
            if (! is_file($file) || (fileperms($file) & 0777) !== 0600) {
                $this->error('Password file must exist and have 0600 permissions.');

                return self::FAILURE;
            }
            $password = trim((string) file_get_contents($file));
        } else {
            $password = $this->secret('Password for the demo logins (input hidden)');
        }
        if (! $password || strlen($password) < 12) {
            $this->error('Password must be at least 12 characters. Provide it via --password-file (0600 file) or the hidden prompt. A password is never generated or printed.');

            return self::FAILURE;
        }

        Permissions::sync();

        [$gym, $owner, $paymentsCount, $checkIns] = DB::transaction(function () use ($password) {
        $gym = Gym::create([
            'name' => 'جيم التجمع',
            'country' => 'EG',
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'locale' => 'ar',
        ]);
        $gym->forceFill(['platform_status' => 'active', 'ai_enabled' => true])->save();

        $owner = User::create([
            'name' => 'مالك حمدي',
            'email' => 'demo@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make($password),
            'gym_id' => $gym->id,
        ]);
        $owner->assignRole(Role::findByName('owner'));

        $reception = User::create([
            'name' => 'سلمى عبد الله',
            'email' => 'reception@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make($password),
            'gym_id' => $gym->id,
        ]);
        $reception->assignRole(Role::findByName('staff'));

        // BelongsToGym fills gym_id from the authenticated user.
        Auth::setUser($owner);

        $tz = 'Africa/Cairo';
        $today = Carbon::today($tz);

        $plans = collect([
            Plan::create(['name' => 'شهري', 'type' => 'duration', 'duration_days' => 30, 'price' => 800, 'freeze_days_allowed' => 7]),
            Plan::create(['name' => '٣ شهور', 'type' => 'duration', 'duration_days' => 90, 'price' => 2000, 'freeze_days_allowed' => 21]),
            Plan::create(['name' => '٦ شهور', 'type' => 'duration', 'duration_days' => 180, 'price' => 3600, 'freeze_days_allowed' => 30]),
            Plan::create(['name' => 'سنوي', 'type' => 'duration', 'duration_days' => 365, 'price' => 6500, 'freeze_days_allowed' => 60]),
            Plan::create(['name' => 'باقة ١٢ حصة', 'type' => 'sessions', 'sessions_count' => 12, 'price' => 1200]),
        ]);

        $firsts = ['أحمد', 'محمد', 'محمود', 'مصطفى', 'كريم', 'عمر', 'يوسف', 'حسن', 'إبراهيم', 'طارق', 'سارة', 'منى', 'نور', 'مريم', 'هدى', 'ريم', 'دينا', 'آية', 'فاطمة', 'ياسمين'];
        $lasts = ['محمود', 'علي', 'حسن', 'إبراهيم', 'خالد', 'سيد', 'عبدالله', 'فتحي', 'رمضان', 'شعبان', 'عادل', 'نجيب', 'صبري', 'حجازي', 'البدري'];

        $members = collect();
        for ($i = 0; $i < 52; $i++) {
            $members->push(Member::create([
                'name' => $firsts[$i % 20].' '.$lasts[($i * 7 + intdiv($i, 20)) % 15],
                'phone' => sprintf('0100%07d', $i + 1),
                'gender' => $i % 20 < 10 ? 'male' : 'female',
                'joined_at' => $today->copy()->subDays(30 + ($i * 5) % 240)->toDateString(),
            ]));
        }

        $methods = ['cash', 'cash', 'cash', 'card', 'wallet'];
        $paymentsCount = 0;

        foreach ($members as $i => $member) {
            // Older, finished subscription for members who joined a while ago.
            if ($i % 3 !== 0) {
                $plan = $plans[$i % 4];
                $start = $today->copy()->subDays(90 + ($i * 3) % 150);
                $end = $start->copy()->addDays($plan->duration_days - 1);
                $old = Subscription::create([
                    'member_id' => $member->id, 'plan_id' => $plan->id,
                    'starts_at' => $start->toDateString(), 'ends_at' => $end->toDateString(),
                    'status' => 'expired', 'price' => $plan->price,
                ]);
                Payment::create(['subscription_id' => $old->id, 'amount' => $plan->price, 'method' => $methods[$i % 5], 'paid_at' => $start->copy()->addDay()->setTime(12, 0)]);
                $paymentsCount++;
            }

            // Current subscription: ~70% active duration, 12% sessions pack, rest expired.
            $roll = $i % 25;
            if ($roll < 17) {
                $plan = $plans[$i % 4];
                $start = $today->copy()->subDays(($i * 2) % max(1, $plan->duration_days - 5));
                $sub = Subscription::create([
                    'member_id' => $member->id, 'plan_id' => $plan->id,
                    'starts_at' => $start->toDateString(),
                    'ends_at' => $start->copy()->addDays($plan->duration_days - 1)->toDateString(),
                    'status' => 'active', 'price' => $plan->price,
                ]);
                // ~1 in 5 is partially paid (debtor), some pay the rest today.
                if ($i % 5 === 0) {
                    Payment::create(['subscription_id' => $sub->id, 'amount' => intdiv($plan->price, 2), 'method' => $methods[$i % 5], 'paid_at' => $start->copy()->addDay()->setTime(13, 0)]);
                    $paymentsCount++;
                    if ($i % 10 === 0) {
                        Payment::create(['subscription_id' => $sub->id, 'amount' => intdiv($plan->price, 4), 'method' => 'cash', 'paid_at' => now($tz)]);
                        $paymentsCount++;
                    }
                } else {
                    Payment::create(['subscription_id' => $sub->id, 'amount' => $plan->price, 'method' => $methods[$i % 5], 'paid_at' => $start->copy()->addDay()->setTime(11, 30)]);
                    $paymentsCount++;
                }
            } elseif ($roll < 20) {
                $start = $today->copy()->subDays(3 + $i % 20);
                $sub = Subscription::create([
                    'member_id' => $member->id, 'plan_id' => $plans[4]->id,
                    'starts_at' => $start->toDateString(),
                    'status' => 'active', 'sessions_remaining' => 3 + ($i % 9), 'price' => $plans[4]->price,
                ]);
                Payment::create(['subscription_id' => $sub->id, 'amount' => $plans[4]->price, 'method' => $methods[$i % 5], 'paid_at' => $start->copy()->setTime(18, 0)]);
                $paymentsCount++;
            } else {
                $plan = $plans[$i % 4];
                $start = $today->copy()->subDays($plan->duration_days + 2 + ($i % 30));
                Subscription::create([
                    'member_id' => $member->id, 'plan_id' => $plan->id,
                    'starts_at' => $start->toDateString(),
                    'ends_at' => $start->copy()->addDays($plan->duration_days - 1)->toDateString(),
                    'status' => 'expired', 'price' => $plan->price,
                ]);
            }
        }

        // Weekly classes, a couple of them today, with some bookings.
        $classDefs = [
            ['كروس فيت', 'كابتن حسن', 18, 0], ['سبينينج', 'كابتن سارة', 19, 0],
            ['يوجا', 'كابتن منى', 20, 1], ['ملاكمة', 'كابتن طارق', 19, 2],
            ['سباحة', 'كابتن يوسف', 17, 3], ['قوة وحديد', 'كابتن حسن', 18, 6],
        ];
        foreach ($classDefs as [$name, $coach, $hour, $weekday]) {
            $class = GymClass::create(['name' => $name, 'coach' => $coach, 'weekday' => $weekday, 'start_time' => sprintf('%02d:00', $hour), 'duration_minutes' => 60, 'capacity' => 20]);
            if ($weekday === (int) $today->dayOfWeek) {
                foreach ($members->take(8 + ($hour % 3)) as $m) {
                    ClassBooking::create(['gym_class_id' => $class->id, 'member_id' => $m->id, 'class_date' => $today->toDateString(), 'status' => 'booked']);
                }
            }
        }

        // Check-ins: spread over the past four weeks (fills date-range reports) plus a burst today.
        $checkIns = 0;
        foreach ($members as $i => $member) {
            $visits = 1 + ($i % 5);
            for ($v = 0; $v < $visits; $v++) {
                $daysAgo = 1 + (($i * 3 + $v * 4) % 29);
                CheckIn::create(['member_id' => $member->id, 'checked_in_at' => $today->copy()->subDays($daysAgo)->setTime(9 + ($i + $v) % 12, ($i * 7) % 60), 'method' => $v % 3 === 0 ? 'manual' : 'qr', 'result' => 'allowed']);
                $checkIns++;
            }
        }
        foreach ($members->take(14) as $i => $member) {
            $deny = $i >= 12;
            CheckIn::create(['member_id' => $member->id, 'checked_in_at' => now($tz)->subHours(1 + $i % 6), 'method' => $i % 2 ? 'qr' : 'manual', 'result' => $deny ? 'denied' : 'allowed', 'denial_reason' => $deny ? 'expired' : null]);
            $checkIns++;
        }

        return [$gym, $owner, $paymentsCount, $checkIns];
        });

        $this->info('Demo gym created: '.$gym->name);
        $this->info('Members: '.Member::count().' | Subscriptions: '.Subscription::count().' | Payments: '.$paymentsCount.' | Check-ins: '.$checkIns);
        $this->info('Owner login:     demo@example.com');
        $this->info('Reception login: reception@example.com');
        $this->info('Password: as provided via --password-file or the hidden prompt (never printed).');

        return self::SUCCESS;
    }
}
