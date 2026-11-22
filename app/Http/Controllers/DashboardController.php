<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Subscription;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        abort_unless(auth()->user()->can('reports.view'), 403);

        $gym = auth()->user()->gym;
        $today = Carbon::today($gym->timezone);

        $activeSubscriptions = Subscription::with('plan')
            ->get()
            ->filter(fn (Subscription $s) => $s->effectiveStatus($today) === Subscription::STATUS_ACTIVE);

        $expiringSoon = Subscription::with(['member', 'plan'])
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [$today->toDateString(), $today->copy()->addDays(7)->toDateString()])
            ->orderBy('ends_at')
            ->limit(10)
            ->get()
            ->map(fn (Subscription $s) => [
                'id' => $s->id,
                'member_id' => $s->member->id,
                'member' => $s->member->name,
                'plan' => $s->plan->name,
                'ends_at' => $s->ends_at->toDateString(),
                'days_left' => $s->daysLeft($today),
            ]);

        $debtors = Member::with(['subscriptions.payments'])
            ->where('status', 'active')
            ->get()
            ->map(fn (Member $m) => ['member_id' => $m->id, 'name' => $m->name, 'balance' => $m->outstandingBalance()])
            ->filter(fn ($row) => $row['balance'] > 0)
            ->sortByDesc('balance')
            ->take(10)
            ->values();

        // Revenue of the last 6 calendar months (gym timezone), grouped in PHP
        // so the query stays identical on SQLite and MySQL.
        $trendStart = $today->copy()->startOfMonth()->subMonths(5);
        $payments6m = Payment::where('paid_at', '>=', $trendStart->copy()->setTimezone('UTC'))->get(['amount', 'paid_at']);
        $revenueTrend = collect(CarbonPeriod::create($trendStart, '1 month', $today->copy()->startOfMonth()))
            ->map(function (Carbon $monthStart) use ($payments6m, $gym) {
                $key = $monthStart->format('Y-m');
                $sum = $payments6m
                    ->filter(fn (Payment $p) => $p->paid_at->copy()->setTimezone($gym->timezone)->format('Y-m') === $key)
                    ->sum('amount');

                return ['month' => $key, 'revenue' => round((float) $sum, 2)];
            })->values();

        // Today's allowed check-ins grouped by hour (gym timezone).
        // "Today" is the gym-local day; timestamps are stored in UTC.
        $todayStartUtc = $today->copy()->setTimezone('UTC');
        $todayEndUtc = $today->copy()->endOfDay()->setTimezone('UTC');
        $todayCheckIns = CheckIn::whereBetween('checked_in_at', [$todayStartUtc, $todayEndUtc])
            ->where('result', 'allowed')
            ->get(['checked_in_at']);
        $byHour = $todayCheckIns
            ->groupBy(fn (CheckIn $c) => $c->checked_in_at->copy()->setTimezone($gym->timezone)->format('H'))
            ->map->count();
        $checkinsByHour = collect(range(0, 23))
            ->map(fn (int $h) => ['hour' => str_pad((string) $h, 2, '0', STR_PAD_LEFT), 'count' => (int) ($byHour[sprintf('%02d', $h)] ?? 0)])
            ->values();

        // Active subscriptions per plan name.
        $planMix = $activeSubscriptions
            ->groupBy(fn (Subscription $s) => $s->plan->name)
            ->map(fn ($group, $name) => ['label' => $name, 'value' => $group->count()])
            ->sortByDesc('value')
            ->take(6)
            ->values();

        $recentCheckIns = CheckIn::with('member')
            ->latest('checked_in_at')
            ->limit(8)
            ->get()
            ->map(fn (CheckIn $c) => [
                'member_id' => $c->member->id,
                'member' => $c->member->name,
                'result' => $c->result,
                'reason' => $c->denial_reason,
                'checked_in_at' => $c->checked_in_at->copy()->setTimezone($gym->timezone)->toDateTimeString(),
            ]);

        return Inertia::render('Dashboard', [
            'stats' => [
                'members' => Member::where('status', 'active')->count(),
                'active_subscriptions' => $activeSubscriptions->count(),
                'checkins_today' => $todayCheckIns->count(),
                'denied_today' => CheckIn::whereBetween('checked_in_at', [$todayStartUtc, $todayEndUtc])->where('result', 'denied')->count(),
                'revenue_month' => (float) Payment::whereBetween('paid_at', [$today->copy()->startOfMonth()->setTimezone('UTC'), $today->copy()->endOfMonth()->endOfDay()->setTimezone('UTC')])->sum('amount'),
                'outstanding_total' => round($debtors->sum('balance'), 2),
            ],
            'expiringSoon' => $expiringSoon,
            'debtors' => $debtors,
            'revenueTrend' => $revenueTrend,
            'checkinsByHour' => $checkinsByHour,
            'planMix' => $planMix,
            'recentCheckIns' => $recentCheckIns,
            'currency' => $gym->currency,
        ]);
    }
}
