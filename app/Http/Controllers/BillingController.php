<?php

namespace App\Http\Controllers;

use App\Models\PlatformInvoice;
use App\Models\PlatformPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BillingController extends Controller
{
    public function show(Request $request)
    {
        $gym = $request->user()->gym;
        $tz = $gym->timezone ?: 'UTC';

        return Inertia::render('Billing/Show', [
            'billing' => [
                'status' => $gym->platform_status,
                'trial_days_left' => $gym->trialDaysLeft(),
                'trial_ends_at' => $gym->trial_ends_at?->toDateString(),
                'renews_at' => $gym->platform_renews_at?->toDateString(),
                'plan' => $gym->platformPlan ? ['name_ar' => $gym->platformPlan->name_ar, 'name_en' => $gym->platformPlan->name_en] : null,
                'sales_whatsapp' => config('platform.sales_whatsapp'),
            ],
            'plans' => PlatformPlan::where('is_active', true)->orderBy('sort_order')->get()
                ->map(fn (PlatformPlan $p) => [
                    'id' => $p->id,
                    'name_ar' => $p->name_ar,
                    'name_en' => $p->name_en,
                    'interval' => $p->interval,
                    'price_cents' => $p->price_cents,
                    'currency' => $p->currency,
                ])->values(),
            'invoices' => $gym->platformInvoices()->with('plan')->orderByDesc('id')->limit(12)->get()
                ->map(fn (PlatformInvoice $i) => [
                    'id' => $i->id,
                    'plan_ar' => $i->plan?->name_ar,
                    'plan_en' => $i->plan?->name_en,
                    'amount_cents' => $i->amount_cents,
                    'currency' => $i->currency,
                    'period' => $i->period_starts_at->toDateString() . ' → ' . $i->period_ends_at->toDateString(),
                    'status' => $i->status,
                ])->values(),
        ]);
    }

    public function subscribe(Request $request)
    {
        abort_unless($request->user()->can('gym.manage'), 403);

        $data = $request->validate([
            'plan_id' => ['required', Rule::exists('platform_plans', 'id')->where('is_active', true)],
        ]);

        $gym = $request->user()->gym;
        $plan = PlatformPlan::findOrFail($data['plan_id']);

        // One open request at a time.
        $open = $gym->platformInvoices()->where('status', 'pending')->latest('id')->first();
        if ($open) {
            return back()->with('success', 'لديك طلب اشتراك قيد المراجعة بالفعل');
        }

        $tz = $gym->timezone ?: 'UTC';
        $start = Carbon::today($tz);
        $end = $plan->interval === 'yearly' ? $start->copy()->addYear() : $start->copy()->addMonth();

        // Manual billing only: records a pending invoice; the platform owner confirms payment.
        $gym->platformInvoices()->create([
            'platform_plan_id' => $plan->id,
            'amount_cents' => $plan->price_cents,
            'currency' => $plan->currency,
            'period_starts_at' => $start,
            'period_ends_at' => $end,
            'status' => 'pending',
        ]);

        return back()->with('success', 'تم تسجيل طلب الاشتراك، سيتم تأكيده بعد مراجعة الدفع');
    }
}
