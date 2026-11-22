<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Inertia\Inertia;

class CardController extends Controller
{
    /**
     * Public member card: what the member shows at the door. Intentionally
     * outside the tenant scope (the scanner is not logged in) and leaks no
     * money data - name, gym, and entry state only.
     */
    public function __invoke(string $token)
    {
        $member = Member::withoutGlobalScopes()->with('gym')->where('qr_token', $token)->firstOrFail();

        $subscription = $member->currentSubscription();
        $today = now($member->gym->timezone);

        return Inertia::render('Card', [
            'member' => [
                'name' => $member->name,
                'status' => $member->status,
            ],
            'gym' => ['name' => $member->gym->name, 'locale' => $member->gym->locale],
            'subscription' => $subscription ? [
                'plan' => $subscription->plan->name,
                'status' => $subscription->effectiveStatus($today),
                'days_left' => $subscription->daysLeft($today),
                'sessions_remaining' => $subscription->sessions_remaining,
            ] : null,
            'token' => $token,
        ]);
    }
}
