<?php

namespace App\Http\Controllers;

use App\Models\Gym;
use App\Models\PlatformInvoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PlatformAdminController extends Controller
{
    public function index()
    {
        $gyms = Gym::with('platformPlan')->withCount(['members', 'users'])->orderByDesc('id')->paginate(30)
            ->through(fn (Gym $g) => [
                'id' => $g->id,
                'name' => $g->name,
                'country' => $g->country,
                'status' => $g->platform_status,
                'trial_days_left' => $g->trialDaysLeft(),
                'renews_at' => $g->platform_renews_at?->toDateString(),
                'plan_ar' => $g->platformPlan?->name_ar,
                'members_count' => $g->members_count,
                'users_count' => $g->users_count,
                'created_at' => $g->created_at?->toDateString(),
            ]);

        return Inertia::render('Platform/Index', ['gyms' => $gyms]);
    }

    public function show(Gym $gym)
    {
        $gym->load('platformPlan');

        return Inertia::render('Platform/Show', [
            'gym' => [
                'id' => $gym->id,
                'name' => $gym->name,
                'status' => $gym->platform_status,
                'trial_ends_at' => $gym->trial_ends_at?->toDateString(),
                'renews_at' => $gym->platform_renews_at?->toDateString(),
                'plan_ar' => $gym->platformPlan?->name_ar,
            ],
            'invoices' => $gym->platformInvoices()->with('plan')->orderByDesc('id')->get()
                ->map(fn (PlatformInvoice $i) => [
                    'id' => $i->id,
                    'plan_ar' => $i->plan?->name_ar,
                    'amount_cents' => $i->amount_cents,
                    'currency' => $i->currency,
                    'period' => $i->period_starts_at->toDateString() . ' → ' . $i->period_ends_at->toDateString(),
                    'status' => $i->status,
                ])->values(),
        ]);
    }

    public function markPaid(Request $request, PlatformInvoice $invoice)
    {
        abort_if($invoice->status === 'paid', 422, 'مدفوعة بالفعل');

        DB::transaction(function () use ($invoice) {
            $invoice->update(['status' => 'paid', 'paid_at' => now()]);
            // Older pending requests for the same gym are superseded.
            PlatformInvoice::where('gym_id', $invoice->gym_id)->where('status', 'pending')->where('id', '!=', $invoice->id)->update(['status' => 'void']);
            $invoice->gym->update([
                'platform_status' => 'active',
                'platform_plan_id' => $invoice->platform_plan_id,
                'platform_renews_at' => $invoice->period_ends_at,
            ]);
        });

        return back()->with('success', 'تم تأكيد الدفع وتفعيل الاشتراك');
    }

    public function extendTrial(Request $request, Gym $gym)
    {
        $data = $request->validate(['days' => 'required|integer|min:1|max:365']);

        $tz = $gym->timezone ?: 'UTC';
        $base = $gym->trial_ends_at && $gym->trial_ends_at->gte(Carbon::today($tz)) ? $gym->trial_ends_at : Carbon::today($tz);
        $gym->update([
            'platform_status' => 'trialing',
            'trial_ends_at' => $base->copy()->addDays($data['days']),
        ]);

        return back()->with('success', 'تم تمديد الفترة التجريبية');
    }

    public function setStatus(Request $request, Gym $gym)
    {
        $data = $request->validate(['status' => 'required|in:active,suspended,past_due']);

        $gym->update(['platform_status' => $data['status']]);

        return back()->with('success', 'تم تحديث حالة الاشتراك');
    }
}
