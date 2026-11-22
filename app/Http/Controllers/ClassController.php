<?php

namespace App\Http\Controllers;

use App\Models\GymClass;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('classes.manage'), 403);

        $tz = $request->user()->gym->timezone;
        $date = $request->date('date')?->toDateString() ?? Carbon::today($tz)->toDateString();
        $weekday = Carbon::parse($date)->dayOfWeek;

        $classes = GymClass::with(['bookings' => fn ($q) => $q->whereDate('class_date', $date)->with('member:id,name,phone')])
            ->orderBy('start_time')
            ->get()
            ->map(fn (GymClass $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'coach' => $c->coach,
                'weekday' => $c->weekday,
                'start_time' => $c->start_time,
                'duration_minutes' => $c->duration_minutes,
                'capacity' => $c->capacity,
                'is_active' => $c->is_active,
                'runs_this_day' => $c->weekday === $weekday,
                'booked' => $c->bookings->where('status', 'booked')->count(),
                'bookings' => $c->bookings->where('status', 'booked')->map(fn ($b) => [
                    'id' => $b->id,
                    'member' => $b->member->name,
                    'phone' => $b->member->phone,
                ])->values(),
            ]);

        return Inertia::render('Classes/Index', [
            'classes' => $classes,
            'date' => $date,
            'weekday' => $weekday,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('classes.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'coach' => ['nullable', 'string', 'max:255'],
            'weekday' => ['required', 'integer', 'min:0', 'max:6'],
            'start_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        GymClass::create($data);

        return back();
    }

    public function update(Request $request, GymClass $class)
    {
        abort_unless($request->user()->can('classes.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'coach' => ['nullable', 'string', 'max:255'],
            'weekday' => ['required', 'integer', 'min:0', 'max:6'],
            'start_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        $class->update($data);

        return back();
    }

    public function destroy(Request $request, GymClass $class)
    {
        abort_unless($request->user()->can('classes.manage'), 403);
        // Keep history: a class with bookings can only be deactivated.
        abort_if($class->bookings()->exists(), 422, 'لا يمكن حذف حصة عليها حجوزات، عطّلها بدلًا');

        $class->delete();

        return back();
    }
}
