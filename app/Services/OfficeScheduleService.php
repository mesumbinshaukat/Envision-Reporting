<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\OfficeClosure;
use App\Models\OfficeSchedule;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class OfficeScheduleService
{
    public const DEFAULT_START_TIME = '09:00';
    public const DEFAULT_END_TIME = '18:00';
    public const DEFAULT_WORKING_DAYS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
    ];

    public function getSchedule(User $user): OfficeSchedule
    {
        $schedule = $user->officeSchedule;

        if ($schedule) {
            return $schedule;
        }

        return new OfficeSchedule([
            'user_id' => $user->id,
            'start_time' => self::DEFAULT_START_TIME,
            'end_time' => self::DEFAULT_END_TIME,
            'working_days' => self::DEFAULT_WORKING_DAYS,
            'timezone' => $user->timezone ?? config('app.timezone'),
        ]);
    }

    public function getClosuresForRange(User $user, Carbon $start, Carbon $end): Collection
    {
        return OfficeClosure::query()
            ->where('user_id', $user->id)
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhere(function ($inner) use ($start, $end) {
                        $inner->whereNotNull('end_date')
                            ->where('start_date', '<=', $end->toDateString())
                            ->where('end_date', '>=', $start->toDateString());
                    })
                    ->orWhere(function ($inner) use ($start, $end) {
                        $inner->whereNull('end_date')
                            ->where('start_date', '<=', $end->toDateString());
                    });
            })
            ->get();
    }

    public function getWorkingDaysBetween(User $user, Carbon $start, Carbon $end, ?Collection $closures = null): Collection
    {
        $closures ??= $this->getClosuresForRange($user, $start, $end);
        $schedule = $this->getSchedule($user);
        $workingDays = collect();

        foreach (CarbonPeriod::create($start->copy(), $end->copy()) as $date) {
            if ($this->isWorkingDay($date, $schedule, $closures)) {
                $workingDays->push($date->copy());
            }
        }

        return $workingDays;
    }

    public function isWorkingDay(Carbon $date, OfficeSchedule $schedule, ?Collection $closures = null): bool
    {
        $workingDays = $schedule->working_days ?: self::DEFAULT_WORKING_DAYS;
        $dayName = strtolower($date->format('l'));

        if (!in_array($dayName, $workingDays, true)) {
            return false;
        }

        if (!$closures || $closures->isEmpty()) {
            return true;
        }

        return !$closures->contains(function (OfficeClosure $closure) use ($date) {
            $start = Carbon::parse($closure->start_date)->startOfDay();
            $end = $closure->end_date
                ? Carbon::parse($closure->end_date)->endOfDay()
                : Carbon::parse($closure->start_date)->endOfDay();

            return $date->betweenIncluded($start, $end);
        });
    }

    public function resolveScheduleWindow(Carbon $date, OfficeSchedule $schedule): ?array
    {
        $startTime = $schedule->start_time ?: self::DEFAULT_START_TIME;
        $endTime = $schedule->end_time ?: self::DEFAULT_END_TIME;
        $timezone = $schedule->timezone ?: config('app.timezone');

        if (!$startTime || !$endTime) {
            return null;
        }

        $start = Carbon::parse($date->toDateString() . ' ' . $startTime, $timezone);
        $end = Carbon::parse($date->toDateString() . ' ' . $endTime, $timezone);
        $crossesMidnight = false;

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
            $crossesMidnight = true;
        }

        return [
            'start' => $start,
            'end' => $end,
            'crosses_midnight' => $crossesMidnight,
            'timezone' => $timezone,
        ];
    }

    public function calculateAttendanceMetrics(Attendance $attendance, array $scheduleWindow): array
    {
        $metrics = [
            'late_minutes' => 0,
            'overtime_minutes' => 0,
            'worked_minutes' => 0,
        ];

        if (!$attendance->check_in || !$attendance->check_out) {
            return $metrics;
        }

        /** @var Carbon $scheduledStart */
        $scheduledStart = $scheduleWindow['start'];
        /** @var Carbon $scheduledEnd */
        $scheduledEnd = $scheduleWindow['end'];

        $checkIn = $attendance->check_in->copy()->setTimezone($scheduledStart->timezone);
        $checkOut = $attendance->check_out->copy()->setTimezone($scheduledEnd->timezone);

        $metrics['worked_minutes'] = $checkIn->diffInMinutes($checkOut);

        if ($checkIn->greaterThan($scheduledStart)) {
            $metrics['late_minutes'] = $scheduledStart->diffInMinutes($checkIn);
        }

        if ($checkOut->greaterThan($scheduledEnd)) {
            $metrics['overtime_minutes'] = $scheduledEnd->diffInMinutes($checkOut);
        }

        return $metrics;
    }
}
