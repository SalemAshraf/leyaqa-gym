<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassBooking;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\MemberPortalSession;
use App\Support\ClassBookingManager;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberApiController extends Controller
{
    /**
     * Exchange the single-use bootstrap token (from the staff-shared link)
     * for a bounded API session token (30 days). One replay is impossible:
     * the bootstrap is consumed at exchange.
     */
    public function createSession(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => 'required|string|max:200']);

        // Explicit user intent (the member tapped sign-in in the app), so the
        // POST consumes - atomically, so a replay or race gets a 401.
        $member = Member::exchangePortalToken($data['token']);
        abort_unless($member, 401, 'Invalid member token');

        [$session, $plain] = MemberPortalSession::mint($member, 'api');

        return response()->json([
            'token' => $plain,
            'expires_at' => $session->expires_at->toIso8601String(),
        ], 201);
    }

    public function profile(Request $request): JsonResponse
    {
        $member = $request->attributes->get('member');
        $gym = $member->gym;
        $today = now($gym->timezone);
        $subscription = $member->currentSubscription();

        return response()->json([
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
            'qr_token' => $member->qr_token,
        ]);
    }

    public function classes(Request $request): JsonResponse
    {
        $member = $request->attributes->get('member');
        $gym = $member->gym;
        $today = now($gym->timezone);

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

        return response()->json(['days' => $days]);
    }

    public function bookings(Request $request): JsonResponse
    {
        $member = $request->attributes->get('member');

        $upcoming = ClassBooking::withoutGlobalScopes()
            ->with('gymClass')
            ->where('member_id', $member->id)
            ->whereDate('class_date', '>=', now($member->gym->timezone)->toDateString())
            ->whereIn('status', ['booked', 'waitlisted'])
            ->orderBy('class_date')
            ->limit(20)
            ->get()
            ->map(fn (ClassBooking $b) => [
                'id' => $b->id,
                'class' => $b->gymClass?->name,
                'date' => $b->class_date->toDateString(),
                'start_time' => $b->gymClass?->start_time,
                'status' => $b->status,
            ]);

        return response()->json(['bookings' => $upcoming]);
    }

    public function book(Request $request, ClassBookingManager $bookings): JsonResponse
    {
        $member = $request->attributes->get('member');

        $data = $request->validate([
            'class_id' => ['required', 'integer'],
            'class_date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $class = GymClass::withoutGlobalScopes()
            ->where('gym_id', $member->gym_id)
            ->findOrFail($data['class_id']);

        abort_if(Carbon::parse($data['class_date'])->gt(now($member->gym->timezone)->addDays(13)->endOfDay()), 422);

        $booking = $bookings->book($class, $member, $data['class_date']);

        return response()->json([
            'booking' => ['id' => $booking->id, 'status' => $booking->status, 'class_date' => $booking->class_date->toDateString()],
        ], 201);
    }

    public function cancel(Request $request, int $bookingId, ClassBookingManager $bookings): JsonResponse
    {
        $member = $request->attributes->get('member');

        $booking = ClassBooking::withoutGlobalScopes()->findOrFail($bookingId);
        abort_unless($booking->member_id === $member->id, 404);
        abort_if($booking->class_date->isPast(), 422);

        $bookings->cancel($booking);

        return response()->json(['cancelled' => true]);
    }
}
