<?php

namespace App\Http\Controllers;

use App\Models\ClassBooking;
use App\Models\GymClass;
use App\Models\Member;
use App\Support\ClassBookingManager;
use Illuminate\Http\Request;

class ClassBookingController extends Controller
{
    public function store(Request $request, GymClass $class, ClassBookingManager $bookings)
    {
        abort_unless($request->user()->can('classes.manage'), 403);

        $data = $request->validate([
            'member_id' => ['required', 'integer'],
            'class_date' => ['required', 'date'],
        ]);

        $member = Member::findOrFail($data['member_id']);
        abort_unless($member->accessibleBy($request->user()), 404);

        $booking = $bookings->book($class, $member, $data['class_date']);

        return back()->with('success', $booking->status === 'booked' ? 'تم حجز مكان العضو' : 'الحصة مكتملة، أُضيف العضو لقائمة الانتظار');
    }

    public function destroy(Request $request, ClassBooking $booking, ClassBookingManager $bookings)
    {
        abort_unless($request->user()->can('classes.manage'), 403);

        $bookings->cancel($booking);

        return back()->with('success', 'تم إلغاء الحجز');
    }
}
