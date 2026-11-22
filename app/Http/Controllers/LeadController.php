<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('members.manage'), 403);

        $status = $request->string('status')->toString();
        $search = $request->string('search')->trim()->toString();

        $leads = Lead::query()
            ->when(in_array($status, Lead::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(fn (Lead $l) => [
                'id' => $l->id,
                'name' => $l->name,
                'phone' => $l->phone,
                'source' => $l->source,
                'notes' => $l->notes,
                'status' => $l->status,
                'member_id' => $l->member_id,
                'created_at' => $l->created_at?->toDateString(),
            ]);

        return Inertia::render('Leads/Index', [
            'leads' => $leads,
            'filters' => ['status' => $status, 'search' => $search],
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('members.manage'), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:40',
            'source' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
        ]);

        Lead::create($data + ['created_by' => $request->user()->id]);

        return back()->with('success', 'تم تسجيل العميل المحتمل');
    }

    public function update(Request $request, Lead $lead)
    {
        abort_unless($request->user()->can('members.manage'), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:40',
            'source' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
            'status' => ['required', Rule::in(Lead::STATUSES)],
        ]);

        // Conversion only happens through convert() so member linkage stays consistent.
        if ($lead->status !== 'converted' && $data['status'] === 'converted') {
            $data['status'] = $lead->status;
        }
        if ($lead->status === 'converted') {
            $data['status'] = 'converted';
        }

        $lead->update($data);

        return back()->with('success', 'تم تحديث بيانات العميل');
    }

    public function destroy(Request $request, Lead $lead)
    {
        abort_unless($request->user()->can('members.manage'), 403);
        abort_if($lead->status === 'converted', 422, 'لا يمكن حذف عميل تحوّل لعضو');

        $lead->delete();

        return back()->with('success', 'تم حذف العميل المحتمل');
    }

    public function convert(Request $request, Lead $lead)
    {
        abort_unless($request->user()->can('members.manage'), 403);

        if ($lead->member_id) {
            return back()->with('success', 'العميل محوّل لعضو بالفعل');
        }

        $member = Member::create([
            'name' => $lead->name,
            'phone' => $lead->phone,
            'notes' => $lead->notes,
            'status' => 'active',
        ]);

        $lead->update(['status' => 'converted', 'member_id' => $member->id]);

        return back()->with('success', 'تم تحويل العميل لعضو، أكمل بياناته وسجّل اشتراكه');
    }
}
