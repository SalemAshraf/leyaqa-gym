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

        $gym = $request->user()->gym;
        $connections = \App\Models\OauthToken::where('gym_id', $gym->id)->whereNull('revoked_at')
            ->get()->groupBy('family')
            ->map(function ($rows) {
                $client = \App\Models\OauthClient::where('client_id', $rows->first()->client_id)->first();

                return [
                    'family' => $rows->first()->family,
                    'app' => $client?->name ?? 'Claude',
                    'connected_at' => $rows->min('created_at'),
                    'last_used_at' => $rows->max('last_used_at'),
                ];
            })->sortByDesc('connected_at')->values();

        return Inertia::render('Settings/Edit', [
            'gym' => $gym->only(['name', 'phone', 'country', 'currency', 'timezone', 'locale']),
            'currencies' => self::CURRENCIES,
            'ai' => ['enabled' => (bool) $gym->ai_enabled, 'connections' => $connections],
            'reminders' => [
                'enabled' => (bool) $gym->reminders_enabled,
                'days_before' => (int) $gym->reminder_days_before,
            ],
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

    /** Automatic member reminders (expiry + debt). Off until the owner opts in. */
    public function remindersToggle(Request $request)
    {
        abort_unless($request->user()->can('gym.manage'), 403);
        $data = $request->validate([
            'enabled' => 'required|boolean',
            'days_before' => 'required|integer|min:1|max:14',
        ]);
        $request->user()->gym->forceFill([
            'reminders_enabled' => $data['enabled'],
            'reminder_days_before' => $data['days_before'],
        ])->save();

        return back()->with('success', 'تم حفظ الإعدادات');
    }

    /** Owner-only on/off switch for the read-only AI connector (Claude over MCP). */
    public function aiToggle(Request $request)
    {
        abort_unless($request->user()->isOwner(), 403);
        $data = $request->validate(['enabled' => 'required|boolean']);
        $request->user()->gym->forceFill(['ai_enabled' => $data['enabled']])->save();

        return back();
    }

    /** Disconnect one connected AI app: every token of the connection is revoked. Always allowed for the owner. */
    public function aiConnectionRevoke(Request $request, string $family)
    {
        abort_unless($request->user()->isOwner(), 403);
        \App\Models\OauthToken::where('gym_id', $request->user()->gym_id)->where('family', $family)->update(['revoked_at' => now()]);

        return back();
    }
}
