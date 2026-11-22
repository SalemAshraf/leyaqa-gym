<?php

namespace App\Http\Controllers;

use App\Models\ClassBooking;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\MemberPortalSession;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;

/**
 * Member self-service portal: private link (portal_token, NOT the public
 * door card token) showing the member's own subscription, balance and
 * class bookings, and letting them book/cancel classes themselves.
 */
class MemberPortalController extends Controller
{
    /**
     * The staff-shared link: a single-use bootstrap token exchanged once for
     * a bounded portal session (web cookie, 30 days). A consumed/expired link
     * with no live session lands on the expired-link page.
     */
    public function exchange(Request $request, string $token)
    {
        // A live session always wins, whatever state the link is in.
        $existing = $request->session()->get('member_portal_session_id');
        $existing = $existing ? MemberPortalSession::find($existing) : null;
        if ($existing?->isValid()) {
            return redirect()->route('portal.show');
        }

        // A GET never consumes the bootstrap: chat link previews and security
        // scanners fetch shared URLs, and that must not burn the one-time
        // credential before the member intentionally signs in. The member
        // confirms with the explicit POST below.
        $member = Member::findByPortalToken($token);
        if (! $member) {
            return redirect()->route('portal.expired');
        }

        return Inertia::render('MemberLinkConfirm', [
            'gym_name' => $member->gym?->name,
            'token' => $token,
        ]);
    }

    /** Explicit member intent: consume the single-use bootstrap (atomic). */
    public function consumeExchange(Request $request, string $token)
    {
        $member = Member::exchangePortalToken($token);

        if (! $member) {
            return redirect()->route('portal.expired');
        }

        [$portalSession] = MemberPortalSession::mint($member, 'web');
        $request->session()->put('member_portal_session_id', $portalSession->id);

        return redirect()->route('portal.show');
    }

    public function show(Request $request)
    {
        $member = $request->attributes->get('member');
        $member->load('gym');
        $gym = $member->gym;
        $today = now($gym->timezone);
        $subscription = $member->currentSubscription();

        // Upcoming 14 days of active classes with this member's booking state.
        $days = [];
        for ($i = 0; $i < 14; $i++) {
            $date = $today->copy()->addDays($i);
            $classes = GymClass::withoutGlobalScopes()
                ->where('gym_id', $gym->id)
                ->where('is_active', true)
                ->where('weekday', $date->dayOfWeek)
                ->orderBy('start_time')
                ->get()
                ->map(function (GymClass $class) use ($date, $member) {
                    $booking = ClassBooking::withoutGlobalScopes()
                        ->where('gym_class_id', $class->id)
                        ->where('member_id', $member->id)
                        ->whereDate('class_date', $date->toDateString())
                        ->whereIn('status', ['booked', 'waitlisted'])
                        ->first();

                    return [
                        'id' => $class->id,
                        'name' => $class->name,
                        'coach' => $class->coach,
                        'start_time' => $class->start_time,
                        'duration_minutes' => $class->duration_minutes,
                        'full' => ! $class->hasSpots($date->toDateString()),
                        'booking_id' => $booking?->id,
                        'booking_status' => $booking?->status,
                    ];
                });
            if ($classes->isNotEmpty()) {
                $days[] = ['date' => $date->toDateString(), 'weekday' => $date->dayOfWeek, 'classes' => $classes->values()];
            }
        }

        $upcoming = ClassBooking::withoutGlobalScopes()
            ->with('gymClass')
            ->where('member_id', $member->id)
            ->whereDate('class_date', '>=', $today->toDateString())
            ->whereIn('status', ['booked', 'waitlisted'])
            ->orderBy('class_date')
            ->limit(10)
            ->get()
            ->map(fn (ClassBooking $b) => [
                'id' => $b->id,
                'class' => $b->gymClass?->name,
                'date' => $b->class_date->toDateString(),
                'start_time' => $b->gymClass?->start_time,
                'status' => $b->status,
            ]);

        return Inertia::render('MemberPortal', [
            'member' => ['name' => $member->name, 'status' => $member->status],
            'gym' => ['name' => $gym->name, 'locale' => $gym->locale, 'currency' => $gym->currency],
            'subscription' => $subscription ? [
                'plan' => $subscription->plan->name,
                'status' => $subscription->effectiveStatus($today),
                'days_left' => $subscription->daysLeft($today),
                'sessions_remaining' => $subscription->sessions_remaining,
                'ends_at' => $subscription->ends_at?->toDateString(),
            ] : null,
            'balance' => $member->outstandingBalance(),
            'upcoming' => $upcoming,
            'days' => $days,
            'card_url' => route('card', $member->qr_token),
        ]);
    }

    /**
     * Web app manifest per member: "add to home screen" opens their portal
     * directly (app-like install without a store listing).
     */
    public function manifest(Request $request): JsonResponse
    {
        $member = $request->attributes->get('member');

        return response()->json([
            'name' => $member->gym->name.' - '.$member->name,
            'short_name' => $member->gym->name,
            'start_url' => route('portal.show'),
            'scope' => route('portal.show'),
            'display' => 'standalone',
            'dir' => $member->gym->locale === 'ar' ? 'rtl' : 'ltr',
            'background_color' => '#0a0a0a',
            'theme_color' => '#0a0a0a',
            'icons' => [
                ['src' => '/icon.svg', 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'any'],
                ['src' => '/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
            ],
        ]);
    }
}
