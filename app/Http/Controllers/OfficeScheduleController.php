<?php

namespace App\Http\Controllers;

use App\Models\OfficeSchedule;
use App\Services\OfficeScheduleService;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class OfficeScheduleController extends Controller
{
    protected const DAY_OPTIONS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    public function edit(Request $request, OfficeScheduleService $scheduleService)
    {
        $user = Auth::user();
        $schedule = $scheduleService->getSchedule($user);

        $timezoneScope = defined(DateTimeZone::class . '::COMMON')
            ? DateTimeZone::COMMON
            : DateTimeZone::ALL;

        $timezones = collect(DateTimeZone::listIdentifiers($timezoneScope));

        return view('admin.attendance.office-schedule.edit', [
            'schedule' => $schedule,
            'dayOptions' => self::DAY_OPTIONS,
            'timezones' => $timezones,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'timezone' => ['nullable', 'timezone'],
            'working_days' => ['required', 'array', 'min:1'],
            'working_days.*' => ['in:' . implode(',', self::DAY_OPTIONS)],
        ]);

        $payload = [
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'timezone' => Arr::get($validated, 'timezone', config('app.timezone')),
            'working_days' => $validated['working_days'],
        ];

        /** @var OfficeSchedule $schedule */
        $schedule = $user->officeSchedule()->updateOrCreate([], $payload);

        return redirect()
            ->route('admin.attendance.office-schedule.edit')
            ->with('success', 'Office schedule updated successfully.');
    }
}
