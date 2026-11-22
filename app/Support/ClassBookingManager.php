<?php

namespace App\Support;

use App\Models\ClassBooking;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Shared booking rules for staff-side and member self-service booking:
 * active member, active class, matching weekday, running subscription,
 * dedupe, capacity with waitlist, and waitlist promotion on cancel.
 */
class ClassBookingManager
{
    /**
     * @throws ValidationException
     */
    public function book(GymClass $class, Member $member, string $date): ClassBooking
    {
        $date = Carbon::parse($date)->toDateString();

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
        if (! $subscription || $subscription->effectiveStatus() !== Subscription::STATUS_ACTIVE) {
            throw ValidationException::withMessages(['member_id' => 'العضو يحتاج اشتراكًا ساريًا']);
        }

        $existing = ClassBooking::where('gym_class_id', $class->id)
            ->where('member_id', $member->id)
            ->whereDate('class_date', $date)
            ->first();

        if ($existing) {
            if ($existing->status === 'booked') {
                throw ValidationException::withMessages(['member_id' => 'العضو محجوز بالفعل']);
            }
            if ($existing->status === 'waitlisted') {
                throw ValidationException::withMessages(['member_id' => 'العضو على قائمة الانتظار بالفعل']);
            }
            $existing->update(['status' => $class->hasSpots($date) ? 'booked' : 'waitlisted']);

            return $existing;
        }

        return ClassBooking::create([
            // Explicit: no authed tenant user exists on the member self-service path.
            'gym_id' => $class->gym_id,
            'gym_class_id' => $class->id,
            'member_id' => $member->id,
            'class_date' => $date,
            'status' => $class->hasSpots($date) ? 'booked' : 'waitlisted',
        ]);
    }

    public function cancel(ClassBooking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $wasBooked = $booking->status === 'booked';
            $booking->update(['status' => 'cancelled']);

            if ($wasBooked) {
                // Promote the earliest waitlisted member into the freed spot.
                ClassBooking::where('gym_class_id', $booking->gym_class_id)
                    ->whereDate('class_date', $booking->class_date)
                    ->where('status', 'waitlisted')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first()
                    ?->update(['status' => 'booked']);
            }
        });
    }
}
