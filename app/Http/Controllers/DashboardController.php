<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Subscription;
use Carbon\Carbon;
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
            ->map(fn (Member $m) => ['id' => $m->id, 'name' => $m->name, 'balance' => $m->outstandingBalance()])
            ->filter(fn ($row) => $row['balance'] > 0)
            ->sortByDesc('balance')
            ->take(10)
            ->values();

        return Inertia::render('Dashboard', [
            'stats' => [
                'members' => Member::where('status', 'active')->count(),
                'active_subscriptions' => $activeSubscriptions->count(),
                'checkins_today' => CheckIn::whereDate('checked_in_at', $today->toDateString())->where('result', 'allowed')->count(),
                'denied_today' => CheckIn::whereDate('checked_in_at', $today->toDateString())->where('result', 'denied')->count(),
                'revenue_month' => (float) Payment::whereBetween('paid_at', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()->endOfDay()])->sum('amount'),
                'outstanding_total' => round($debtors->sum('balance'), 2),
            ],
            'expiringSoon' => $expiringSoon,
            'debtors' => $debtors,
            'currency' => $gym->currency,
        ]);
    }
}
