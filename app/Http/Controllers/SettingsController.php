<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SettingsController extends Controller
{
    private const CURRENCIES = ['EGP', 'SAR', 'AED', 'KWD', 'QAR', 'BHD', 'OMR', 'USD'];

    public function edit(Request $request)
    {
        abort_unless($request->user()->can('gym.manage'), 403);

        return Inertia::render('Settings/Edit', [
            'gym' => $request->user()->gym->only(['name', 'phone', 'country', 'currency', 'timezone', 'locale']),
            'currencies' => self::CURRENCIES,
        ]);
    }

    public function update(Request $request)
    {
        abort_unless($request->user()->can('gym.manage'), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:40',
            'country' => 'required|string|size:2',
            'currency' => ['required', Rule::in(self::CURRENCIES)],
            'timezone' => 'required|timezone:all',
        ]);

        $request->user()->gym->update($data);

        return back()->with('success', 'تم حفظ الإعدادات');
    }
}
