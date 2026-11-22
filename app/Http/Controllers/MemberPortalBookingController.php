<?php

namespace App\Http\Controllers;

use App\Models\ClassBooking;
use App\Models\GymClass;
use App\Models\Member;
use App\Support\ClassBookingManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MemberPortalBookingController extends Controller
{
    public function store(Request $request, string $token, ClassBookingManager $bookings)
    {
        $member = Member::findByPortalToken($token) ?? abort(404);

        $data = $request->validate([
            'class_id' => ['required', 'integer'],
            'class_date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $class = GymClass::withoutGlobalScopes()
            ->where('gym_id', $member->gym_id)
            ->findOrFail($data['class_id']);

        // Self-service horizon: same 14 days the portal shows.
        abort_if(Carbon::parse($data['class_date'])->gt(now($member->gym->timezone)->addDays(13)->endOfDay()), 422);

        $booking = $bookings->book($class, $member, $data['class_date']);

        return back()->with('success', $booking->status === 'booked' ? 'تم حجز مكانك' : 'الحصة مكتملة، أنت على قائمة الانتظار');
    }

    public function destroy(Request $request, string $token, int $bookingId, ClassBookingManager $bookings)
    {
        $member = Member::findByPortalToken($token) ?? abort(404);

        // No tenant context on this public route: resolve without the gym scope,
        // then authorize strictly by ownership.
        $booking = ClassBooking::withoutGlobalScopes()->findOrFail($bookingId);
        abort_unless($booking->member_id === $member->id, 404);
        abort_if($booking->class_date->isPast(), 422);

        $bookings->cancel($booking);

        return back()->with('success', 'تم إلغاء الحجز');
    }
}
