<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\V1\AttendanceResource;
use App\Http\Traits\ApiPagination;
use App\Http\Traits\ApiFiltering;
use App\Http\Traits\ApiSorting;
use App\Models\Attendance;
use App\Models\EmployeeUser;
use App\Services\GeolocationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceApiController extends BaseApiController
{
    use ApiPagination, ApiFiltering, ApiSorting;

    /**
     * Display a listing of attendance records
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        if ($user->tokenCan('admin')) {
            // Admin sees all attendance for their organization
            $employeeUserIds = EmployeeUser::where('admin_id', $user->id)->pluck('id');
            $query = Attendance::whereIn('employee_user_id', $employeeUserIds);
        } else {
            // Employee sees only their own attendance
            $query = Attendance::where('employee_user_id', $user->id);
        }

        $query->with(['employeeUser.employee']);

        // Apply filters
        $this->applyFilters($query, [
            'attendance_date' => 'date',
            'employee_user_id' => '=',
        ]);

        // Date range filter
        if ($request->has('date_from')) {
            $query->whereDate('attendance_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('attendance_date', '<=', $request->date_to);
        }

        // Apply sorting
        $this->applySorting($query, [
            'id', 'attendance_date', 'check_in', 'check_out', 'created_at'
        ], 'attendance_date', 'desc');

        $attendances = $this->applyPagination($query);

        return $this->paginated($attendances, AttendanceResource::class);
    }

    /**
     * Check in
     *
     * @param Request $request
     * @param GeolocationService $geoService
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkIn(Request $request, GeolocationService $geoService)
    {
        if (!$request->user()->tokenCan('employee')) {
            return $this->forbidden('Only employees can check in');
        }

        $validated = $request->validate([
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $employeeUser = $request->user();
        $employeeUser->load('employee');

        $today = Carbon::today();
        
        // Check if already checked in today
        $existingAttendance = Attendance::where('employee_user_id', $employeeUser->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if ($existingAttendance && $existingAttendance->hasCheckedIn()) {
            return $this->error('You have already checked in today', 400);
        }

        // Validate geolocation if required
        if ($employeeUser->employee && $employeeUser->employee->requiresActiveGeolocation()) {
            if (!isset($validated['latitude']) || !isset($validated['longitude'])) {
                return $this->error('Geolocation is required for check-in', 400);
            }

            $distance = $geoService->calculateDistance(
                $validated['latitude'],
                $validated['longitude']
            );

            if (!$geoService->isWithinOfficeRadius($distance)) {
                return $this->error('You are not within the office radius', 403);
            }
        }

        // Create or update attendance
        $attendance = $existingAttendance ?? new Attendance();
        $attendance->employee_user_id = $employeeUser->id;
        $attendance->attendance_date = $today;
        $attendance->check_in = Carbon::now();
        $attendance->check_in_latitude = $validated['latitude'] ?? null;
        $attendance->check_in_longitude = $validated['longitude'] ?? null;
        $attendance->check_in_ip = $request->ip();
        $attendance->check_in_user_agent = $request->userAgent();
        
        if (isset($validated['latitude']) && isset($validated['longitude'])) {
            $attendance->check_in_distance_meters = $geoService->calculateDistance(
                $validated['latitude'],
                $validated['longitude']
            );
        }

        $attendance->save();

        return $this->resource(new AttendanceResource($attendance), 'Checked in successfully', 201);
    }

    /**
     * Check out
     *
     * @param Request $request
     * @param GeolocationService $geoService
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkOut(Request $request, GeolocationService $geoService)
    {
        if (!$request->user()->tokenCan('employee')) {
            return $this->forbidden('Only employees can check out');
        }

        $validated = $request->validate([
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $employeeUser = $request->user();
        $today = Carbon::today();

        $attendance = Attendance::where('employee_user_id', $employeeUser->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if (!$attendance || !$attendance->hasCheckedIn()) {
            return $this->error('You must check in before checking out', 400);
        }

        if ($attendance->hasCheckedOut()) {
            return $this->error('You have already checked out today', 400);
        }

        $attendance->check_out = Carbon::now();
        $attendance->check_out_latitude = $validated['latitude'] ?? null;
        $attendance->check_out_longitude = $validated['longitude'] ?? null;
        $attendance->check_out_ip = $request->ip();
        $attendance->check_out_user_agent = $request->userAgent();
        
        if (isset($validated['latitude']) && isset($validated['longitude'])) {
            $attendance->check_out_distance_meters = $geoService->calculateDistance(
                $validated['latitude'],
                $validated['longitude']
            );
        }

        $attendance->save();

        return $this->resource(new AttendanceResource($attendance), 'Checked out successfully');
    }

    /**
     * Display the specified attendance
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        $query = Attendance::with(['employeeUser.employee', 'fixRequests', 'logs']);

        if ($user->tokenCan('admin')) {
            $employeeUserIds = EmployeeUser::where('admin_id', $user->id)->pluck('id');
            $query->whereIn('employee_user_id', $employeeUserIds);
        } else {
            $query->where('employee_user_id', $user->id);
        }

        $attendance = $query->find($id);

        if (!$attendance) {
            return $this->notFound('Attendance record not found');
        }

        return $this->resource(new AttendanceResource($attendance));
    }

    /**
     * Get current attendance status for the authenticated employee
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurrentStatus(Request $request)
    {
        $user = $request->user();
        
        // Get employee user ID based on token type
        if ($user->tokenCan('employee')) {
            $employeeUserId = $user->id;
        } elseif ($user->tokenCan('admin')) {
            // Admin can check status for a specific employee
            $request->validate([
                'employee_user_id' => 'required|exists:employee_users,id',
            ]);
            $employeeUserId = $request->employee_user_id;
            
            // Verify the employee belongs to this admin
            $employeeUser = EmployeeUser::where('id', $employeeUserId)
                ->where('admin_id', $user->id)
                ->first();
            
            if (!$employeeUser) {
                return $this->forbidden('You do not have access to this employee');
            }
        } else {
            return $this->forbidden('Invalid token permissions');
        }

        $today = Carbon::today();
        
        // Get today's attendance
        $todayAttendance = Attendance::where('employee_user_id', $employeeUserId)
            ->whereDate('attendance_date', $today)
            ->first();

        // Get any pending attendance (checked in but not checked out)
        $pendingAttendance = Attendance::where('employee_user_id', $employeeUserId)
            ->whereNull('check_out')
            ->orderByDesc('attendance_date')
            ->first();

        $status = [
            'today_date' => $today->toDateString(),
            'checked_in_today' => $todayAttendance && $todayAttendance->hasCheckedIn(),
            'checked_out_today' => $todayAttendance && $todayAttendance->hasCheckedOut(),
            'can_check_in' => !$todayAttendance || !$todayAttendance->hasCheckedIn(),
            'can_check_out' => $todayAttendance && $todayAttendance->hasCheckedIn() && !$todayAttendance->hasCheckedOut(),
            'has_pending_checkout' => $pendingAttendance && $pendingAttendance->attendance_date->isBefore($today),
        ];

        if ($todayAttendance) {
            $status['today_attendance'] = new AttendanceResource($todayAttendance);
        }

        if ($pendingAttendance && $pendingAttendance->attendance_date->isBefore($today)) {
            $status['pending_attendance'] = [
                'id' => $pendingAttendance->id,
                'date' => $pendingAttendance->attendance_date->toDateString(),
                'check_in' => $pendingAttendance->check_in?->toDateTimeString(),
            ];
        }

        return $this->success($status, 'Current attendance status retrieved successfully');
    }

    /**
     * Get attendance statistics
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics(Request $request)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can view statistics');
        }

        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'employee_user_id' => 'nullable|exists:employee_users,id',
        ]);

        $employeeUserIds = EmployeeUser::where('admin_id', $request->user()->id)->pluck('id');
        $query = Attendance::whereIn('employee_user_id', $employeeUserIds);

        if ($request->has('date_from')) {
            $query->whereDate('attendance_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('attendance_date', '<=', $request->date_to);
        }
        if ($request->has('employee_user_id')) {
            $query->where('employee_user_id', $request->employee_user_id);
        }

        $totalRecords = $query->count();
        $completedRecords = $query->clone()->completed()->count();
        $pendingCheckouts = $query->clone()->checkedInOnly()->count();
        $avgWorkDuration = $query->clone()->completed()->get()->avg('work_duration');

        return $this->success([
            'total_records' => $totalRecords,
            'completed_records' => $completedRecords,
            'pending_checkouts' => $pendingCheckouts,
            'average_work_duration_hours' => round($avgWorkDuration, 2),
        ]);
    }
}
