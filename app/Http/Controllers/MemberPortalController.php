<?php

namespace App\Http\Controllers;

use App\Models\ClassBooking;
use App\Models\GymClass;
use App\Models\Member;
use Carbon\Carbon;
use Inertia\Inertia;

/**
 * Member self-service portal: private link (portal_token, NOT the public
 * door card token) showing the member's own subscription, balance and
 * class bookings, and letting them book/cancel classes themselves.
 */
class MemberPortalController extends Controller
{
    public function show(string $token)
    {
        $member = Member::withoutGlobalScopes()->with('gym')->where('portal_token', $token)->firstOrFail();
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
            'token' => $token,
            'card_url' => route('card', $member->qr_token),
        ]);
    }
}
