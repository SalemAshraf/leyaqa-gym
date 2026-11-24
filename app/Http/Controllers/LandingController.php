<?php

namespace App\Http\Controllers;

use App\Models\PlatformPlan;
use Inertia\Inertia;

class LandingController extends Controller
{
    public function __invoke()
    {
        if (auth()->check()) {
            return redirect()->route(auth()->user()->isPlatformAdmin() ? 'platform.index' : 'dashboard');
        }

        return Inertia::render('Welcome', [
            'canRegister' => true,
            'trialDays' => config('platform.trial_days'),
            'salesWhatsapp' => config('platform.sales_whatsapp'),
            'platformPlans' => PlatformPlan::where('is_active', true)->orderBy('sort_order')->get()
                ->map(fn (PlatformPlan $p) => [
                    'name_ar' => $p->name_ar,
                    'name_en' => $p->name_en,
                    'interval' => $p->interval,
                    'price_cents' => $p->price_cents,
                    'currency' => $p->currency,
                ])->values(),
        ]);
    }
}
