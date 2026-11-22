<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Gym;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'gym_name' => 'required|string|max:255',
            'country' => ['required', Rule::in(['EG', 'SA', 'AE', 'KW', 'QA', 'BH', 'OM'])],
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $currencies = ['EG' => 'EGP', 'SA' => 'SAR', 'AE' => 'AED', 'KW' => 'KWD', 'QA' => 'QAR', 'BH' => 'BHD', 'OM' => 'OMR'];
        $timezones = ['EG' => 'Africa/Cairo', 'SA' => 'Asia/Riyadh', 'AE' => 'Asia/Dubai', 'KW' => 'Asia/Kuwait', 'QA' => 'Asia/Qatar', 'BH' => 'Asia/Bahrain', 'OM' => 'Asia/Muscat'];

        $user = DB::transaction(function () use ($request, $currencies, $timezones) {
            Permissions::sync();

            $gym = Gym::create([
                'name' => $request->gym_name,
                'country' => $request->country,
                'currency' => $currencies[$request->country],
                'timezone' => $timezones[$request->country],
                'locale' => 'ar',
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'gym_id' => $gym->id,
            ]);

            $user->assignRole(Role::findByName('owner'));

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
