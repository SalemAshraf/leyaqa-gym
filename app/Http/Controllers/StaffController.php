<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    private function guard(Request $request): void
    {
        abort_unless($request->user()->can('staff.manage'), 403);
    }

    public function index(Request $request)
    {
        $this->guard($request);

        return Inertia::render('Staff/Index', [
            'staff' => User::where('gym_id', $request->user()->gym_id)
                ->get()
                ->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'phone' => $u->phone,
                    'role' => $u->roles->first()?->name,
                    'is_you' => $u->id === $request->user()->id,
                ]),
        ]);
    }

    public function store(Request $request)
    {
        $this->guard($request);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:40',
            'password' => ['required', Password::defaults()],
            'role' => ['required', Rule::in(['owner', 'staff'])],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'gym_id' => $request->user()->gym_id,
        ]);

        $user->assignRole(Role::findByName($data['role']));

        return back()->with('success', 'تم إضافة الموظف');
    }

    public function destroy(Request $request, User $user)
    {
        $this->guard($request);

        abort_unless($user->gym_id === $request->user()->gym_id, 404);
        abort_if($user->id === $request->user()->id, 422, 'لا يمكنك حذف حسابك');

        $user->delete();

        return back()->with('success', 'تم حذف الموظف');
    }
}
