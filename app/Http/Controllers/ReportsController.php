<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Subscription;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Inertia\Inertia;

class ReportsController extends Controller
{
    public function __invoke()
    {
        abort_unless(auth()->user()->can('reports.view'), 403);

        $gym = auth()->user()->gym;
        $tz = $gym->timezone;
        $today = Carbon::today($tz);
        $monthStart = $today->copy()->startOfMonth();
        $monthStartUtc = $monthStart->copy()->setTimezone('UTC');
        $nowUtc = now('UTC');

        // Revenue, last 12 gym-local months (grouped in PHP: SQLite/MySQL identical).
        $trendStart = $monthStart->copy()->subMonths(11);
        $payments12m = Payment::where('paid_at', '>=', $trendStart->copy()->setTimezone('UTC'))->get(['amount', 'paid_at', 'method']);
        $revenueTrend = collect(CarbonPeriod::create($trendStart, '1 month', $monthStart))
            ->map(function (Carbon $m) use ($payments12m, $tz) {
                $key = $m->format('Y-m');

                return ['month' => $key, 'revenue' => round((float) $payments12m
                    ->filter(fn (Payment $p) => $p->paid_at->copy()->setTimezone($tz)->format('Y-m') === $key)
                    ->sum('amount'), 2)];
            })->values();

        // Check-ins per gym-local day, last 30 days.
        $daysStart = $today->copy()->subDays(29);
        $checkIns30 = CheckIn::where('checked_in_at', '>=', $daysStart->copy()->setTimezone('UTC'))
            ->where('result', 'allowed')
            ->get(['checked_in_at']);
        $byDay = $checkIns30->groupBy(fn (CheckIn $c) => $c->checked_in_at->copy()->setTimezone($tz)->toDateString())->map->count();
        $checkinsPerDay = collect(CarbonPeriod::create($daysStart, $today))
            ->map(fn (Carbon $d) => ['day' => $d->toDateString(), 'count' => (int) ($byDay[$d->toDateString()] ?? 0)])
            ->values();

        // This month's payments by method.
        $byMethod = $payments12m
            ->filter(fn (Payment $p) => $p->paid_at->between($monthStartUtc, $nowUtc))
            ->groupBy('method')
            ->map(fn ($g, $m) => ['label' => $m, 'value' => round((float) $g->sum('amount'), 2)])
            ->sortByDesc('value')->values();

        // New members per month, last 6 months.
        $members = Member::where('joined_at', '>=', $monthStart->copy()->subMonths(5)->toDateString())->get(['joined_at']);
        $newMembers = collect(CarbonPeriod::create($monthStart->copy()->subMonths(5), '1 month', $monthStart))
            ->map(function (Carbon $m) use ($members) {
                $key = $m->format('Y-m');

                return ['month' => $key, 'count' => $members->filter(fn (Member $x) => $x->joined_at->format('Y-m') === $key)->count()];
            })->values();

        // Subscription statuses right now.
        $statuses = Subscription::with('plan')->get()
            ->groupBy(fn (Subscription $s) => $s->effectiveStatus($today))
            ->map->count();

        $prevMonthStart = $monthStart->copy()->subMonth();
        $prevMonthRevenue = $payments12m
            ->filter(fn (Payment $p) => $p->paid_at->copy()->setTimezone($tz)->format('Y-m') === $prevMonthStart->format('Y-m'))
            ->sum('amount');

        return Inertia::render('Reports/Index', [
            'kpis' => [
                'revenue_month' => round((float) $payments12m->filter(fn (Payment $p) => $p->paid_at->between($monthStartUtc, $nowUtc))->sum('amount'), 2),
                'revenue_prev_month' => round((float) $prevMonthRevenue, 2),
                'members_new_month' => $members->filter(fn (Member $x) => $x->joined_at->format('Y-m') === $today->format('Y-m'))->count(),
                'checkins_month' => $checkIns30->filter(fn (CheckIn $c) => $c->checked_in_at->copy()->setTimezone($tz) >= $monthStart)->count(),
            ],
            'revenueTrend' => $revenueTrend,
            'checkinsPerDay' => $checkinsPerDay,
            'byMethod' => $byMethod,
            'newMembers' => $newMembers,
            'statuses' => collect(['active', 'upcoming', 'frozen', 'expired', 'cancelled'])
                ->map(fn ($s) => ['label' => $s, 'value' => (int) ($statuses[$s] ?? 0)])->values(),
            'currency' => $gym->currency,
        ]);
    }
}
