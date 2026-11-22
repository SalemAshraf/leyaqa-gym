<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('plans.manage'), 403);

        return Inertia::render('Plans/Index', [
            'plans' => Plan::withCount('subscriptions')->orderBy('name')->get(),
            'currency' => $request->user()->gym->currency,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('plans.manage'), 403);

        $data = $this->validatePlan($request);
        Plan::create($data);

        return back()->with('success', 'تم إضافة الباقة');
    }

    public function update(Request $request, Plan $plan)
    {
        abort_unless($request->user()->can('plans.manage'), 403);

        $plan->update($this->validatePlan($request));

        return back()->with('success', 'تم تحديث الباقة');
    }

    private function validatePlan(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in([Plan::TYPE_DURATION, Plan::TYPE_SESSIONS])],
            'duration_days' => 'nullable|integer|min:1|max:3650|required_if:type,'.Plan::TYPE_DURATION,
            'sessions_count' => 'nullable|integer|min:1|max:10000|required_if:type,'.Plan::TYPE_SESSIONS,
            'price' => 'required|numeric|min:0|max:10000000',
            'freeze_days_allowed' => 'nullable|integer|min:0|max:365',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['freeze_days_allowed'] = $data['freeze_days_allowed'] ?? 0;

        return $data;
    }
}
