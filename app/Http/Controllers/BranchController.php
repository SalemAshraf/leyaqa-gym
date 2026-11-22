<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BranchController extends Controller
{
    private function guard(Request $request): void
    {
        abort_unless($request->user()->can('gym.manage'), 403);
    }

    public function index(Request $request)
    {
        $this->guard($request);

        return Inertia::render('Branches/Index', [
            'branches' => Branch::withCount(['members', 'staff'])
                ->orderBy('name')
                ->get()
                ->map(fn (Branch $b) => [
                    'id' => $b->id,
                    'name' => $b->name,
                    'address' => $b->address,
                    'phone' => $b->phone,
                    'members_count' => $b->members_count,
                    'staff_count' => $b->staff_count,
                ]),
        ]);
    }

    public function store(Request $request)
    {
        $this->guard($request);

        $gymId = $request->user()->gym_id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('branches', 'name')->where('gym_id', $gymId)],
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:40',
        ]);

        Branch::create($data);

        return back()->with('success', 'تم إضافة الفرع');
    }

    public function update(Request $request, Branch $branch)
    {
        $this->guard($request);

        $gymId = $request->user()->gym_id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('branches', 'name')->where('gym_id', $gymId)->ignore($branch->id)],
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:40',
        ]);

        $branch->update($data);

        return back()->with('success', 'تم تحديث الفرع');
    }

    public function destroy(Request $request, Branch $branch)
    {
        $this->guard($request);

        if ($branch->members()->exists() || $branch->staff()->exists()) {
            return back()->withErrors(['branch' => 'branch_has_people']);
        }

        $branch->delete();

        return back()->with('success', 'تم حذف الفرع');
    }
}
