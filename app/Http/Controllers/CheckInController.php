<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CheckInController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('checkins.manage'), 403);

        $gymTz = $request->user()->gym->timezone;
        // Day boundaries in the gym's timezone, compared against UTC-stored timestamps.
        $todayStartUtc = now($gymTz)->startOfDay()->setTimezone('UTC');
        $todayEndUtc = now($gymTz)->endOfDay()->setTimezone('UTC');

        return Inertia::render('CheckIns/Index', [
            'today' => CheckIn::with('member')
                ->whereBetween('checked_in_at', [$todayStartUtc, $todayEndUtc])
                ->when($request->user()->branch_id && ! $request->user()->hasRole('owner'),
                    fn ($q) => $q->where(fn ($w) => $w->where('branch_id', $request->user()->branch_id)->orWhereNull('branch_id')))
                ->orderByDesc('checked_in_at')
                ->limit(50)
                ->get()
                ->map(fn (CheckIn $c) => [
                    'id' => $c->id,
                    'member' => $c->member->name,
                    'checked_in_at' => $c->checked_in_at->copy()->setTimezone($request->user()->gym->timezone)->toDateTimeString(),
                    'result' => $c->result,
                    'denial_reason' => $c->denial_reason,
                    'method' => $c->method,
                ]),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('checkins.manage'), 403);

        $data = $request->validate([
            'qr_token' => 'nullable|string',
            'member_id' => 'nullable|integer',
        ]);

        $member = null;
        if (! empty($data['qr_token'])) {
            $member = Member::where('qr_token', $data['qr_token'])->first();
        } elseif (! empty($data['member_id'])) {
            $member = Member::find($data['member_id']);
        }

        abort_unless($member, 404, 'العضو غير موجود');
        // Branch-pinned staff cannot check in members of another branch.
        abort_if($request->user()->branch_id && ! $request->user()->hasRole('owner')
            && $member->branch_id && $member->branch_id !== $request->user()->branch_id, 404, 'العضو غير موجود');
        abort_if($member->status !== 'active', 422, 'العضو غير نشط');

        $result = DB::transaction(function () use ($member, $data) {
            // Double-scan guard: a second scan of the same member within 2 minutes
            // returns the original result without a new record or session decrement.
            $recent = CheckIn::where('member_id', $member->id)
                ->where('checked_in_at', '>=', now()->subMinutes(2))
                ->latest('checked_in_at')
                ->first();

            if ($recent) {
                $subscription = $member->currentSubscription();

                return [
                    'allowed' => $recent->result === 'allowed',
                    'reason' => $recent->denial_reason,
                    'member' => $member->name,
                    'balance' => $member->outstandingBalance(),
                    'days_left' => $subscription?->daysLeft(),
                    'sessions_remaining' => $subscription?->sessions_remaining,
                    'duplicate' => true,
                ];
            }

            $subscription = $member->currentSubscription();
            [$allowed, $reason] = $subscription
                ? $subscription->canEnter()
                : [false, 'no_subscription'];

            CheckIn::create([
                'member_id' => $member->id,
                'branch_id' => $member->branch_id,
                'checked_in_at' => now(),
                'method' => empty($data['qr_token']) ? 'manual' : 'qr',
                'result' => $allowed ? 'allowed' : 'denied',
                'denial_reason' => $allowed ? null : $reason,
            ]);

            if ($allowed && $subscription->plan->isSessions()) {
                $subscription->decrement('sessions_remaining');
            }

            return [
                'allowed' => $allowed,
                'reason' => $reason,
                'member' => $member->name,
                'balance' => $member->outstandingBalance(),
                'days_left' => $subscription?->daysLeft(),
                'sessions_remaining' => $subscription?->fresh()->sessions_remaining,
            ];
        });

        return back()->with('checkin', $result);
    }
}
