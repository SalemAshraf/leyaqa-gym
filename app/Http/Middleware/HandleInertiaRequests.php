<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->roles->first()?->name,
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ] : null,
            ],
            'gym' => $user?->gym?->only(['name', 'currency', 'locale', 'timezone']),
            'platform' => $user ? [
                'status' => $user->gym?->platform_status,
                'trial_days_left' => $user->gym?->trialDaysLeft(),
                'is_admin' => $user->isPlatformAdmin(),
            ] : null,
            'locale' => $user?->locale ?? $request->session()->get('locale') ?? $user?->gym?->locale ?? 'ar',
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'checkin' => fn () => $request->session()->get('checkin'),
                'portal_link' => fn () => $request->session()->get('portal_link'),
            ],
        ];
    }
}
