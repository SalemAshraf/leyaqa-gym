<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Subscription;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportsController extends Controller
{
    private const MAX_RANGE_DAYS = 366;

    public function __invoke(Request $request)
    {
        abort_unless(auth()->user()->can('reports.view'), 403);

        $gym = auth()->user()->gym;
        $tz = $gym->timezone;
        $today = Carbon::today($tz);

        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $to = isset($validated['to']) ? Carbon::parse($validated['to'], $tz)->startOfDay() : $today->copy();
        $from = isset($validated['from']) ? Carbon::parse($validated['from'], $tz)->startOfDay() : $to->copy()->startOfMonth();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }
        if ($to->greaterThan($today)) {
            $to = $today->copy();
        }
        if ($from->lessThan($to->copy()->subDays(self::MAX_RANGE_DAYS - 1))) {
            $from = $to->copy()->subDays(self::MAX_RANGE_DAYS - 1);
        }

        $fromUtc = $from->copy()->setTimezone('UTC');
        $toUtc = $to->copy()->endOfDay()->setTimezone('UTC');

        // ---- Payments in range (eager-loaded for detail rows, grouped in PHP: SQLite/MySQL identical).
        $payments = Payment::with(['subscription.member:id,name', 'subscription.plan:id,name'])
            ->whereBetween('paid_at', [$fromUtc, $toUtc])
            ->orderByDesc('paid_at')
            ->get(['id', 'subscription_id', 'amount', 'method', 'paid_at']);

        $revenueTotal = round((float) $payments->sum('amount'), 2);

        $revenueDays = $payments->groupBy(fn (Payment $p) => $p->paid_at->copy()->setTimezone($tz)->toDateString());
        $revenueByDay = collect(CarbonPeriod::create($from, $to))
            ->map(function (Carbon $d) use ($revenueDays) {
                $day = $d->toDateString();
                $rows = $revenueDays[$day] ?? collect();

                return ['day' => $day, 'revenue' => round((float) $rows->sum('amount'), 2), 'count' => $rows->count()];
            })->values();

        $byMethod = $payments->groupBy('method')
            ->map(fn ($g, $m) => ['label' => $m, 'value' => round((float) $g->sum('amount'), 2), 'count' => $g->count()])
            ->sortByDesc('value')->values();

        $byPlan = $payments->groupBy(fn (Payment $p) => $p->subscription?->plan?->name ?: '—')
            ->map(fn ($g, $name) => ['label' => $name, 'value' => round((float) $g->sum('amount'), 2), 'count' => $g->count()])
            ->sortByDesc('value')->values();

        $latestPayments = $payments->take(12)->map(fn (Payment $p) => [
            'id' => $p->id,
            'member' => $p->subscription?->member?->name ?? '—',
            'member_id' => $p->subscription?->member?->id,
            'plan' => $p->subscription?->plan?->name ?? '—',
            'method' => $p->method,
            'amount' => round((float) $p->amount, 2),
            'paid_at' => $p->paid_at->copy()->setTimezone($tz)->format('Y-m-d H:i'),
        ])->values();

        // ---- Attendance in range.
        $checkIns = CheckIn::with('member:id,name')
            ->whereBetween('checked_in_at', [$fromUtc, $toUtc])
            ->get(['id', 'member_id', 'checked_in_at', 'result']);

        $allowed = $checkIns->where('result', 'allowed');
        $checkinDays = $allowed->groupBy(fn (CheckIn $c) => $c->checked_in_at->copy()->setTimezone($tz)->toDateString());
        $checkinsByDay = collect(CarbonPeriod::create($from, $to))
            ->map(fn (Carbon $d) => ['day' => $d->toDateString(), 'count' => (int) ($checkinDays[$d->toDateString()] ?? collect())->count()])
            ->values();

        $checkinsByHour = collect(range(0, 23))->map(function (int $h) use ($allowed, $tz) {
            return ['hour' => str_pad((string) $h, 2, '0', STR_PAD_LEFT), 'count' => $allowed->filter(
                fn (CheckIn $c) => (int) $c->checked_in_at->copy()->setTimezone($tz)->format('G') === $h
            )->count()];
        })->values();

        $topAttendees = $allowed->groupBy('member_id')
            ->map(fn ($g, $memberId) => [
                'member_id' => (int) $memberId,
                'member' => $g->first()->member?->name ?? '—',
                'visits' => $g->count(),
            ])->sortByDesc('visits')->take(10)->values();

        // ---- Subscriptions and members in range.
        $newSubs = Subscription::whereBetween('created_at', [$fromUtc, $toUtc])->count();
        $expiredSubs = Subscription::whereNotNull('ends_at')->whereBetween('ends_at', [$from->toDateString(), $to->toDateString()])->count();
        $newMembers = Member::whereBetween('joined_at', [$from->toDateString(), $to->toDateString()])->count();

        $statuses = Subscription::with('plan')->get()
            ->groupBy(fn (Subscription $s) => $s->effectiveStatus($today))
            ->map->count();

        $rangeDays = $from->diffInDays($to) + 1;

        return Inertia::render('Reports/Index', [
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'kpis' => [
                'revenue' => $revenueTotal,
                'avg_day' => round($revenueTotal / $rangeDays, 2),
                'payments_count' => $payments->count(),
                'checkins_allowed' => $allowed->count(),
                'checkins_denied' => $checkIns->where('result', '!=', 'allowed')->count(),
                'unique_members' => $allowed->pluck('member_id')->unique()->count(),
                'new_members' => $newMembers,
                'new_subscriptions' => $newSubs,
            ],
            'revenueByDay' => $revenueByDay,
            'byMethod' => $byMethod,
            'byPlan' => $byPlan,
            'latestPayments' => $latestPayments,
            'checkinsByDay' => $checkinsByDay,
            'checkinsByHour' => $checkinsByHour,
            'topAttendees' => $topAttendees,
            'subMovement' => ['new' => $newSubs, 'expired' => $expiredSubs],
            'statuses' => collect(['active', 'upcoming', 'frozen', 'expired', 'cancelled'])
                ->map(fn ($s) => ['label' => $s, 'value' => (int) ($statuses[$s] ?? 0)])->values(),
            'currency' => $gym->currency,
        ]);
    }
}
