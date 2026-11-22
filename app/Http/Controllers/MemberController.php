<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('members.manage'), 403);

        $query = Member::query()->with(['subscriptions.payments', 'subscriptions.plan']);

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $members = $query->orderByDesc('created_at')->paginate(20)->through(function (Member $m) {
            $current = $m->currentSubscription();

            return [
                'id' => $m->id,
                'name' => $m->name,
                'phone' => $m->phone,
                'status' => $m->status,
                'joined_at' => $m->joined_at?->toDateString(),
                'balance' => $m->outstandingBalance(),
                'current_subscription' => $current ? [
                    'id' => $current->id,
                    'plan' => $current->plan->name,
                    'status' => $current->effectiveStatus(),
                    'ends_at' => $current->ends_at?->toDateString(),
                    'days_left' => $current->daysLeft(),
                    'sessions_remaining' => $current->sessions_remaining,
                ] : null,
            ];
        });

        return Inertia::render('Members/Index', [
            'members' => $members,
            'filters' => ['search' => $request->string('search')->toString()],
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('members.manage'), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:40',
            'email' => 'nullable|email|max:255',
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'birthdate' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        Member::create($data);

        return back()->with('success', 'تم إضافة العضو');
    }

    public function show(Request $request, Member $member)
    {
        abort_unless($request->user()->can('members.manage'), 403);

        $member->load(['subscriptions.plan', 'subscriptions.payments', 'checkIns' => fn ($q) => $q->latest('checked_in_at')->limit(20)]);

        return Inertia::render('Members/Show', [
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
                'phone' => $member->phone,
                'email' => $member->email,
                'gender' => $member->gender,
                'birthdate' => $member->birthdate?->toDateString(),
                'notes' => $member->notes,
                'status' => $member->status,
                'joined_at' => $member->joined_at?->toDateString(),
                'qr_token' => $member->qr_token,
                'balance' => $member->outstandingBalance(),
            ],
            'subscriptions' => $member->subscriptions->sortByDesc('starts_at')->values()->map(fn ($s) => [
                'id' => $s->id,
                'plan' => $s->plan->name,
                'starts_at' => $s->starts_at->toDateString(),
                'ends_at' => $s->ends_at?->toDateString(),
                'status' => $s->effectiveStatus(),
                'price' => (float) $s->price,
                'paid' => $s->paidAmount(),
                'balance' => $s->balance(),
                'sessions_remaining' => $s->sessions_remaining,
                'freeze_days_used' => $s->freeze_days_used,
                'freeze_days_allowed' => $s->plan->freeze_days_allowed,
            ]),
            'checkIns' => $member->checkIns->map(fn ($c) => [
                'checked_in_at' => $c->checked_in_at->toDateTimeString(),
                'result' => $c->result,
                'denial_reason' => $c->denial_reason,
                'method' => $c->method,
            ]),
            'plans' => Plan::where('is_active', true)->orderBy('name')->get(['id', 'name', 'type', 'duration_days', 'sessions_count', 'price']),
            'currency' => $request->user()->gym->currency,
        ]);
    }

    public function update(Request $request, Member $member)
    {
        abort_unless($request->user()->can('members.manage'), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:40',
            'email' => 'nullable|email|max:255',
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'birthdate' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $member->update($data);

        return back()->with('success', 'تم تحديث بيانات العضو');
    }
}
