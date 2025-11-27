<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\OfficeSchedule;
use App\Models\EmployeeUser;
use App\Services\OfficeScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class OfficeScheduleApiController extends BaseApiController
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

    public function __construct(protected OfficeScheduleService $scheduleService)
    {
    }

    /**
     * Get office schedule
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSchedule(Request $request)
    {
        $user = $request->user();
        
        // Get admin user based on token type
        if ($user->tokenCan('admin')) {
            $admin = $user;
        } elseif ($user->tokenCan('employee')) {
            // Employee user - get their admin
            $employeeUser = EmployeeUser::with('employee.user')->find($user->id);
            
            if (!$employeeUser || !$employeeUser->employee || !$employeeUser->employee->user) {
                return $this->error('Unable to retrieve admin settings', 404);
            }
            
            $admin = $employeeUser->employee->user;
        } else {
            return $this->forbidden('Invalid token permissions');
        }

        $schedule = $this->scheduleService->getSchedule($admin);

        if (!$schedule) {
            return $this->success([
                'schedule' => null,
                'has_schedule' => false,
                'default_schedule' => [
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                    'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                    'timezone' => config('app.timezone'),
                ],
            ], 'No office schedule configured');
        }

        return $this->success([
            'schedule' => [
                'id' => $schedule->id,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'working_days' => $schedule->working_days,
                'timezone' => $schedule->timezone,
                'created_at' => $schedule->created_at?->toISOString(),
                'updated_at' => $schedule->updated_at?->toISOString(),
            ],
            'has_schedule' => true,
        ], 'Office schedule retrieved successfully');
    }

    /**
     * Update office schedule
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSchedule(Request $request)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can update office schedule');
        }

        $validated = $request->validate([
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'timezone' => ['nullable', 'timezone'],
            'working_days' => ['required', 'array', 'min:1'],
            'working_days.*' => ['in:' . implode(',', self::DAY_OPTIONS)],
        ]);

        // Validate that end_time is after start_time
        if ($validated['start_time'] >= $validated['end_time']) {
            return $this->error('End time must be after start time', 422);
        }

        $payload = [
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'timezone' => Arr::get($validated, 'timezone', config('app.timezone')),
            'working_days' => $validated['working_days'],
        ];

        $user = $request->user();
        $schedule = $user->officeSchedule()->updateOrCreate([], $payload);

        return $this->success([
            'schedule' => [
                'id' => $schedule->id,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'working_days' => $schedule->working_days,
                'timezone' => $schedule->timezone,
                'created_at' => $schedule->created_at?->toISOString(),
                'updated_at' => $schedule->updated_at?->toISOString(),
            ],
        ], 'Office schedule updated successfully');
    }
}
