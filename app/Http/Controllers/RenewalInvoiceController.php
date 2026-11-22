<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\RenewalInvoice;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Due renewal invoices: manual collection only. Collecting records a normal
 * Payment on the subscription; the renewal of dates stays the staff's
 * existing renew action. Nothing is charged automatically.
 */
class RenewalInvoiceController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('payments.manage'), 403);

        $invoices = RenewalInvoice::with(['member:id,name,branch_id', 'subscription:id,plan_id', 'subscription.plan:id,name'])
            ->when($request->string('status')->trim()->toString() ?: null, fn ($q, $s) => $q->where('status', $s))
            ->latest('due_date')
            ->paginate(20)
            ->through(fn (RenewalInvoice $i) => [
                'id' => $i->id,
                'member' => $i->member?->name,
                'branch_id' => $i->member?->branch_id,
                'plan' => $i->subscription?->plan?->name,
                'amount' => $i->amount,
                'status' => $i->status,
                'due_date' => $i->due_date->toDateString(),
                'collected_at' => $i->collected_at?->toDateString(),
            ]);

        return Inertia::render('RenewalInvoices/Index', [
            'invoices' => $invoices,
            'status' => $request->string('status')->trim()->toString(),
        ]);
    }

    public function collect(Request $request, RenewalInvoice $renewalInvoice)
    {
        abort_unless($request->user()->can('payments.manage'), 403);
        abort_unless($renewalInvoice->member?->accessibleBy($request->user()), 404);
        abort_unless($renewalInvoice->status === 'due', 422);

        // gym_id comes from the BelongsToGym creating hook (authed staff).
        Payment::create([
            'subscription_id' => $renewalInvoice->subscription_id,
            'amount' => $renewalInvoice->amount,
            'method' => 'cash',
            'paid_at' => now(),
            'note' => 'renewal-invoice:'.$renewalInvoice->id,
            'created_by' => $request->user()->id,
        ]);
        $renewalInvoice->update([
            'status' => 'collected',
            'collected_at' => now(),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'تم تسجيل التحصيل');
    }

    public function cancel(Request $request, RenewalInvoice $renewalInvoice)
    {
        abort_unless($request->user()->can('payments.manage'), 403);
        abort_unless($renewalInvoice->member?->accessibleBy($request->user()), 404);
        abort_unless($renewalInvoice->status === 'due', 422);

        $renewalInvoice->update(['status' => 'cancelled']);

        return back()->with('success', 'تم إلغاء الفاتورة');
    }
}
