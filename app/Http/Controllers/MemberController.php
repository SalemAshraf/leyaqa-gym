<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Member;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MemberController extends Controller
{
    /**
     * LIKE patterns tolerant of Arabic letter variants (hamza/alef, yaa, taa marbuta),
     * so "احمد" also matches "أحمد".
     */
    private static function arabicSearchPatterns(string $q): array
    {
        $groups = [
            ["\u{0627}", "\u{0623}", "\u{0625}", "\u{0622}", "\u{0671}"], // alef variants
            ["\u{064A}", "\u{0649}", "\u{0626}"],                          // yaa / alef maqsura / hamza-on-yaa
            ["\u{0648}", "\u{0624}"],                                       // waw / hamza-on-waw
            ["\u{0647}", "\u{0629}"],                                       // haa / taa marbuta
        ];

        $patterns = [$q];
        foreach ($groups as $group) {
            $next = [];
            foreach ($patterns as $pattern) {
                $occurrences = [];
                foreach ($group as $ch) {
                    $pos = 0;
                    while (($pos = mb_strpos($pattern, $ch, $pos)) !== false) {
                        $occurrences[] = [$pos, $ch];
                        $pos += mb_strlen($ch);
                    }
                }
                // Replace ONE occurrence at a time so words with two matching letters
                // (e.g. ابراهيم) produce every mixed variant.
                foreach ($occurrences as [$pos, $ch]) {
                    foreach ($group as $rep) {
                        $next[] = mb_substr($pattern, 0, $pos) . $rep . mb_substr($pattern, $pos + mb_strlen($ch));
                    }
                }
            }
            $patterns = array_values(array_unique(array_merge($patterns, $next)));
            if (count($patterns) > 64) {
                $patterns = array_slice($patterns, 0, 64);
                break;
            }
        }

        return $patterns;
    }

    private static function applyNameSearch($query, string $q): void
    {
        $query->where(function ($w) use ($q) {
            foreach (self::arabicSearchPatterns($q) as $pattern) {
                $w->orWhere('name', 'like', "%{$pattern}%");
            }
            $w->orWhere('phone', 'like', "%{$q}%");
        });
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->can('members.manage'), 403);

        $query = Member::query()->with(['subscriptions.payments', 'subscriptions.plan', 'branch'])
            ->visibleTo($request->user());

        if ($search = $request->string('search')->trim()->toString()) {
            self::applyNameSearch($query, $search);
        }

        if ($branchId = $request->integer('branch_id')) {
            $query->where('branch_id', $branchId);
        }

        $members = $query->orderByDesc('created_at')->paginate(20)->through(function (Member $m) {
            $current = $m->currentSubscription();

            return [
                'id' => $m->id,
                'name' => $m->name,
                'phone' => $m->phone,
                'status' => $m->status,
                'joined_at' => $m->joined_at?->toDateString(),
                'branch' => $m->branch?->name,
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

        $user = $request->user();

        return Inertia::render('Members/Index', [
            'members' => $members,
            'branches' => (! $user->branch_id || $user->hasRole('owner')) ? Branch::orderBy('name')->get(['id', 'name']) : [],
            'filters' => [
                'search' => $request->string('search')->toString(),
                'branch_id' => $request->integer('branch_id') ?: null,
            ],
        ]);
    }

    public function search(Request $request)
    {
        abort_unless($request->user()->can('members.manage'), 403);

        $q = trim((string) $request->query('q', ''));

        return Member::query()
            ->visibleTo($request->user())
            ->where('status', 'active')
            ->when($q !== '', fn ($query) => self::applyNameSearch($query, $q))
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'phone']);
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
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('gym_id', $request->user()->gym_id)],
        ]);

        Member::create($data);

        return back()->with('success', 'تم إضافة العضو');
    }

    public function show(Request $request, Member $member)
    {
        abort_unless($request->user()->can('members.manage'), 403);
        abort_unless($member->accessibleBy($request->user()), 404);

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
                'branch_id' => $member->branch_id,
                'branch' => $member->branch?->name,
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
                'checked_in_at' => $c->checked_in_at->copy()->setTimezone($request->user()->gym->timezone)->toDateTimeString(),
                'result' => $c->result,
                'denial_reason' => $c->denial_reason,
                'method' => $c->method,
            ]),
            'plans' => Plan::where('is_active', true)->orderBy('name')->get(['id', 'name', 'type', 'duration_days', 'sessions_count', 'price']),
            'branches' => (! $request->user()->branch_id || $request->user()->hasRole('owner')) ? Branch::orderBy('name')->get(['id', 'name']) : [],
            'currency' => $request->user()->gym->currency,
        ]);
    }

    public function update(Request $request, Member $member)
    {
        abort_unless($request->user()->can('members.manage'), 403);
        abort_unless($member->accessibleBy($request->user()), 404);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:40',
            'email' => 'nullable|email|max:255',
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'birthdate' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('gym_id', $request->user()->gym_id)],
        ]);

        $member->update($data);

        return back()->with('success', 'تم تحديث بيانات العضو');
    }

    /**
     * Mint a fresh private portal link for the member. The previous link is
     * revoked immediately; the new URL flashes once for staff to copy/send.
     */
    public function rotatePortalLink(Request $request, Member $member)
    {
        abort_unless($request->user()->can('members.manage'), 403);
        abort_unless($member->accessibleBy($request->user()), 404);

        $plain = $member->rotatePortalToken();

        return back()->with('portal_link', route('portal.show', $plain));
    }
}
