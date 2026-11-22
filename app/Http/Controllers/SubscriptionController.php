<?php

namespace App\Http\Controllers;

use App\Models\Freeze;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    private function guard(Request $request): void
    {
        abort_unless($request->user()->can('subscriptions.manage'), 403);
    }

    public function index(Request $request)
    {
        $this->guard($request);

        $subscriptions = Subscription::with(['member', 'plan', 'payments'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(fn (Subscription $s) => [
                'id' => $s->id,
                'member' => ['id' => $s->member->id, 'name' => $s->member->name],
                'plan' => $s->plan->name,
                'starts_at' => $s->starts_at->toDateString(),
                'ends_at' => $s->ends_at?->toDateString(),
                'status' => $s->effectiveStatus(),
                'price' => (float) $s->price,
                'balance' => $s->balance(),
                'sessions_remaining' => $s->sessions_remaining,
            ]);

        return Inertia::render('Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'currency' => $request->user()->gym->currency,
        ]);
    }

    public function store(Request $request)
    {
        $this->guard($request);

        $data = $request->validate([
            'member_id' => 'required|exists:members,id',
            'plan_id' => 'required|exists:plans,id',
            'starts_at' => 'required|date',
            'price' => 'nullable|numeric|min:0|max:10000000',
            'notes' => 'nullable|string|max:2000',
        ]);

        // BelongsToGym global scope guarantees these belong to the caller's gym.
        $member = Member::findOrFail($data['member_id']);
        $plan = Plan::findOrFail($data['plan_id']);
        abort_unless($plan->is_active, 422, 'الباقة غير مفعّلة');

        $starts = Carbon::parse($data['starts_at']);
        $ends = $plan->isDuration()
            ? $starts->copy()->addDays($plan->duration_days - 1)
            : null;

        Subscription::create([
            'member_id' => $member->id,
            'plan_id' => $plan->id,
            'starts_at' => $starts->toDateString(),
            'ends_at' => $ends?->toDateString(),
            'status' => Subscription::STATUS_ACTIVE,
            'sessions_remaining' => $plan->isSessions() ? $plan->sessions_count : null,
            'price' => $data['price'] ?? $plan->price,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'تم إنشاء الاشتراك');
    }

    public function freeze(Request $request, Subscription $subscription)
    {
        $this->guard($request);

        $data = $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        abort_unless($subscription->effectiveStatus() === Subscription::STATUS_ACTIVE, 422, 'لا يمكن تجميد هذا الاشتراك');

        $days = (int) $data['days'];
        $remaining = $subscription->plan->freeze_days_allowed - $subscription->freeze_days_used;
        abort_if($remaining <= 0, 422, 'لا توجد أيام تجميد متبقية في هذه الباقة');
        abort_if($days > $remaining, 422, "الحد الأقصى المتبقي للتجميد هو {$remaining} يوم");

        DB::transaction(function () use ($subscription, $days, $request) {
            $startsOn = Carbon::today();
            Freeze::create([
                'subscription_id' => $subscription->id,
                'starts_on' => $startsOn->toDateString(),
                'ends_on' => $startsOn->copy()->addDays($days - 1)->toDateString(),
                'days' => $days,
                'created_by' => $request->user()->id,
            ]);

            $subscription->update([
                'status' => Subscription::STATUS_FROZEN,
                'freeze_days_used' => $subscription->freeze_days_used + $days,
                'ends_at' => $subscription->ends_at?->addDays($days),
            ]);
        });

        return back()->with('success', "تم تجميد الاشتراك {$days} يوم");
    }

    public function unfreeze(Request $request, Subscription $subscription)
    {
        $this->guard($request);

        abort_unless($subscription->status === Subscription::STATUS_FROZEN, 422, 'الاشتراك ليس مجمّدًا');

        DB::transaction(function () use ($subscription) {
            $freeze = $subscription->freezes()->latest('id')->first();
            $today = Carbon::today();

            if ($freeze) {
                // Re-credit unused freeze days and pull the end date back.
                $usedDays = max((int) $freeze->starts_on->diffInDays($today), 0);
                $unused = $freeze->days - $usedDays;
                if ($unused > 0) {
                    $subscription->freeze_days_used -= $unused;
                    $subscription->ends_at = $subscription->ends_at?->subDays($unused);
                }
                $freeze->update(['ends_on' => $today->toDateString(), 'days' => $usedDays]);
            }

            $subscription->status = Subscription::STATUS_ACTIVE;
            $subscription->save();
        });

        return back()->with('success', 'تم إلغاء التجميد');
    }

    public function cancel(Request $request, Subscription $subscription)
    {
        $this->guard($request);

        abort_if(in_array($subscription->status, [Subscription::STATUS_CANCELLED, Subscription::STATUS_EXPIRED]), 422, 'الاشتراك منتهي بالفعل');

        $subscription->update(['status' => Subscription::STATUS_CANCELLED]);

        return back()->with('success', 'تم إلغاء الاشتراك');
    }

    public function renew(Request $request, Subscription $subscription)
    {
        $this->guard($request);

        $data = $request->validate([
            'plan_id' => 'nullable|exists:plans,id',
            'price' => 'nullable|numeric|min:0|max:10000000',
        ]);

        $plan = isset($data['plan_id']) ? Plan::findOrFail($data['plan_id']) : $subscription->plan;
        abort_unless($plan->is_active, 422, 'الباقة غير مفعّلة');

        // Renew continues from the day after the current end, or today if already over.
        $today = Carbon::today();
        $starts = $subscription->ends_at && $subscription->ends_at->greaterThanOrEqualTo($today)
            ? $subscription->ends_at->copy()->addDay()
            : $today->copy();

        $ends = $plan->isDuration() ? $starts->copy()->addDays($plan->duration_days - 1) : null;

        Subscription::create([
            'member_id' => $subscription->member_id,
            'plan_id' => $plan->id,
            'starts_at' => $starts->toDateString(),
            'ends_at' => $ends?->toDateString(),
            'status' => Subscription::STATUS_ACTIVE,
            'sessions_remaining' => $plan->isSessions() ? $plan->sessions_count : null,
            'price' => $data['price'] ?? $plan->price,
        ]);

        return back()->with('success', 'تم تجديد الاشتراك');
    }
}
