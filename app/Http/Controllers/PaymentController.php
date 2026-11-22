<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('payments.manage'), 403);

        $payments = Payment::with(['subscription.member'])
            ->orderByDesc('paid_at')
            ->paginate(25)
            ->through(fn (Payment $p) => [
                'id' => $p->id,
                'member' => $p->subscription->member->name,
                'subscription_id' => $p->subscription_id,
                'amount' => (float) $p->amount,
                'method' => $p->method,
                'paid_at' => $p->paid_at->toDateTimeString(),
                'note' => $p->note,
                'can_delete' => $request->user()->isOwner(),
            ]);

        return Inertia::render('Payments/Index', [
            'payments' => $payments,
            'currency' => $request->user()->gym->currency,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('payments.manage'), 403);

        $data = $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'amount' => 'required|numeric|min:0.01|max:10000000',
            'method' => ['required', Rule::in(Payment::METHODS)],
            'paid_at' => 'nullable|date',
            'note' => 'nullable|string|max:500',
        ]);

        $subscription = Subscription::findOrFail($data['subscription_id']);
        abort_if($subscription->status === Subscription::STATUS_CANCELLED, 422, 'لا يمكن تسجيل دفعة على اشتراك ملغي');

        // A cycle settled through its renewal invoice must not be collected
        // again through the normal payment flow.
        $settled = \App\Models\RenewalInvoice::withoutGlobalScopes()
            ->where('subscription_id', $subscription->id)
            ->where('status', 'collected')
            ->exists();
        abort_if($settled && $subscription->paidAmount() >= $subscription->price, 422, 'هذه الدورة محصلة بالفعل عبر فاتورة التجديد');

        Payment::create([
            'subscription_id' => $subscription->id,
            'amount' => $data['amount'],
            'method' => $data['method'],
            'paid_at' => $data['paid_at'] ?? now(),
            'note' => $data['note'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'تم تسجيل الدفعة');
    }

    public function destroy(Request $request, Payment $payment)
    {
        abort_unless($request->user()->isOwner(), 403);

        $payment->delete();

        return back()->with('success', 'تم حذف الدفعة');
    }
}
