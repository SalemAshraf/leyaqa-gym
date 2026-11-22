<?php

namespace App\Http\Controllers;

use App\Models\ClassBooking;
use App\Models\GymClass;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClassBookingController extends Controller
{
    public function store(Request $request, GymClass $class)
    {
        abort_unless($request->user()->can('classes.manage'), 403);

        $data = $request->validate([
            'member_id' => ['required', 'integer'],
            'class_date' => ['required', 'date'],
        ]);

        $member = Member::findOrFail($data['member_id']);
        $date = Carbon::parse($data['class_date'])->toDateString();

        if ($member->status !== 'active') {
            throw ValidationException::withMessages(['member_id' => 'العضو غير نشط']);
        }
        if (! $class->is_active) {
            throw ValidationException::withMessages(['member_id' => 'الحصة غير مفعّلة']);
        }
        if (Carbon::parse($date)->dayOfWeek !== $class->weekday) {
            throw ValidationException::withMessages(['member_id' => 'الحصة لا تقام في هذا اليوم']);
        }

        $subscription = $member->currentSubscription();
        if (! $subscription || $subscription->effectiveStatus() !== \App\Models\Subscription::STATUS_ACTIVE) {
            throw ValidationException::withMessages(['member_id' => 'العضو يحتاج اشتراكًا ساريًا']);
        }

        $existing = ClassBooking::where('gym_class_id', $class->id)
            ->where('member_id', $member->id)
            ->whereDate('class_date', $date)
            ->first();

        if ($existing) {
            if ($existing->status !== 'cancelled') {
                throw ValidationException::withMessages(['member_id' => 'العضو محجوز بالفعل']);
            }
            $existing->update(['status' => 'booked']);

            return back();
        }

        if (! $class->hasSpots($date)) {
            throw ValidationException::withMessages(['member_id' => 'الحصة مكتملة العدد']);
        }

        ClassBooking::create([
            'gym_class_id' => $class->id,
            'member_id' => $member->id,
            'class_date' => $date,
            'status' => 'booked',
        ]);

        return back();
    }

    public function destroy(Request $request, ClassBooking $booking)
    {
        abort_unless($request->user()->can('classes.manage'), 403);

        $booking->update(['status' => 'cancelled']);

        return back();
    }
}
