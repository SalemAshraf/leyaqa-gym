<?php

namespace App\Console\Commands;

use App\Models\Gym;
use App\Models\Member;
use App\Models\ReminderLog;
use App\Models\Subscription;
use App\Services\Messaging\Messaging;
use Illuminate\Console\Command;

/**
 * Automatic member reminders: subscription expiry and outstanding debt.
 * Runs hourly and fires for a gym when its LOCAL time hits 10:00, so each
 * gym's members get reminded at a sensible hour. Only gyms that opted in
 * (reminders_enabled) are touched; the default messaging driver is 'log',
 * which sends nothing externally until a real provider is configured.
 */
class SendGymReminders extends Command
{
    protected $signature = 'gym:send-reminders {--hour= : Override the local send hour (testing)}';

    protected $description = 'Send due subscription-expiry and debt reminders for gyms that enabled them';

    public function handle(): int
    {
        $sendHour = (int) ($this->option('hour') ?? 10);
        $sent = 0;

        Gym::where('reminders_enabled', true)->each(function (Gym $gym) use ($sendHour, &$sent) {
            $now = now($gym->timezone);
            if ($now->hour !== $sendHour) {
                return;
            }
            $sent += $this->sendExpiryReminders($gym, $now);
            $sent += $this->sendDebtReminders($gym, $now);
        });

        $this->info("Reminders sent: {$sent}");

        return self::SUCCESS;
    }

    private function sendExpiryReminders(Gym $gym, $now): int
    {
        $target = $now->copy()->addDays($gym->reminder_days_before)->toDateString();

        $subscriptions = Subscription::withoutGlobalScopes()
            ->with(['member', 'plan'])
            ->where('gym_id', $gym->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereDate('ends_at', $target)
            ->get();

        $sent = 0;
        foreach ($subscriptions as $subscription) {
            $member = $subscription->member;
            if (! $member || $member->status !== 'active' || ! $member->phone) {
                continue;
            }

            $body = $gym->locale === 'ar'
                ? "يا {$member->name}، اشتراكك ({$subscription->plan->name}) في {$gym->name} بينتهي خلال {$gym->reminder_days_before} يوم ({$subscription->ends_at->toDateString()}). جدّد قبل الانتهاء 💪"
                : "Hi {$member->name}, your {$subscription->plan->name} subscription at {$gym->name} ends in {$gym->reminder_days_before} days ({$subscription->ends_at->toDateString()}). Renew before it lapses.";

            $sent += $this->deliver($gym, $member, 'expiry', $subscription->ends_at->toDateString(), $body);
        }

        return $sent;
    }

    private function sendDebtReminders(Gym $gym, $now): int
    {
        $weekStart = $now->copy()->startOfWeek()->toDateString();
        $sent = 0;

        Member::withoutGlobalScopes()
            ->where('gym_id', $gym->id)
            ->where('status', 'active')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->chunk(100, function ($members) use ($gym, $weekStart, &$sent) {
                foreach ($members as $member) {
                    $balance = $member->outstandingBalance();
                    if ($balance <= 0) {
                        continue;
                    }

                    $amount = number_format($balance, 2).' '.$gym->currency;
                    $body = $gym->locale === 'ar'
                        ? "يا {$member->name}، عليك متبقّي {$amount} في {$gym->name}. برجاء السداد في أقرب زيارة."
                        : "Hi {$member->name}, you have {$amount} outstanding at {$gym->name}. Please settle on your next visit.";

                    $sent += $this->deliver($gym, $member, 'debt', $weekStart, $body);
                }
            });

        return $sent;
    }

    /**
     * Dedupe by (member, kind, reference_date): send only when no log row
     * exists, and record the log only after a successful send.
     */
    private function deliver(Gym $gym, Member $member, string $kind, string $referenceDate, string $body): int
    {
        $exists = ReminderLog::withoutGlobalScopes()
            ->where('member_id', $member->id)
            ->where('kind', $kind)
            ->whereDate('reference_date', $referenceDate)
            ->exists();
        if ($exists) {
            return 0;
        }

        $provider = Messaging::provider();
        try {
            $provider->send($gym, $member->phone, $body);
        } catch (\Throwable $e) {
            $this->warn("Reminder failed for member {$member->id}: {$e->getMessage()}");

            return 0;
        }

        ReminderLog::create([
            'gym_id' => $gym->id,
            'member_id' => $member->id,
            'kind' => $kind,
            'reference_date' => $referenceDate,
            'channel' => $provider->channel(),
            'body' => $body,
        ]);

        return 1;
    }
}
