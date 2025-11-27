<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Traits\ApiPagination;
use App\Http\Traits\ApiFiltering;
use App\Http\Traits\ApiSorting;
use App\Models\AttendanceFixRequest;
use App\Models\Attendance;
use App\Models\EmployeeUser;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FixRequestApiController extends BaseApiController
{
    use ApiPagination, ApiFiltering, ApiSorting;

    /**
     * Display a listing of fix requests
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        if ($user->tokenCan('admin')) {
            // Admin sees all fix requests for their employees
            $employeeUserIds = EmployeeUser::where('admin_id', $user->id)->pluck('id');
            $query = AttendanceFixRequest::with(['employeeUser.employee', 'attendance', 'processedBy'])
                ->whereIn('employee_user_id', $employeeUserIds);
        } elseif ($user->tokenCan('employee')) {
            // Employee sees only their own fix requests
            $query = AttendanceFixRequest::with(['attendance', 'processedBy'])
                ->where('employee_user_id', $user->id);
        } else {
            return $this->forbidden('Invalid token permissions');
        }

        // Apply filters
        $this->applyFilters($query, [
            'status' => '=',
            'employee_user_id' => '=',
        ]);

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Apply sorting - prioritize pending requests
        if (!$request->has('sort')) {
            $query->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
                ->orderBy('created_at', 'desc');
        } else {
            $this->applySorting($query, [
                'id', 'status', 'created_at', 'processed_at'
            ], 'created_at', 'desc');
        }

        $fixRequests = $this->applyPagination($query);

        return $this->paginated($fixRequests, function ($fixRequest) use ($user) {
            $data = [
                'id' => $fixRequest->id,
                'employee_user_id' => $fixRequest->employee_user_id,
                'attendance_id' => $fixRequest->attendance_id,
                'reason' => $fixRequest->reason,
                'status' => $fixRequest->status,
                'admin_notes' => $fixRequest->admin_notes,
                'processed_at' => $fixRequest->processed_at?->toISOString(),
                'created_at' => $fixRequest->created_at?->toISOString(),
                'updated_at' => $fixRequest->updated_at?->toISOString(),
            ];

            // Add employee name for admin
            if ($user->tokenCan('admin')) {
                $data['employee_name'] = $fixRequest->employeeUser?->employee?->name ?? $fixRequest->employeeUser?->name ?? 'Unknown';
            }

            // Add processor info if processed
            if ($fixRequest->processed_at && $fixRequest->processedBy) {
                $data['processed_by'] = [
                    'id' => $fixRequest->processedBy->id,
                    'name' => $fixRequest->processedBy->name,
                ];
            }

            // Add attendance info
            if ($fixRequest->attendance) {
                $data['attendance'] = [
                    'id' => $fixRequest->attendance->id,
                    'attendance_date' => $fixRequest->attendance->attendance_date?->toDateString(),
                    'check_in' => $fixRequest->attendance->check_in?->toISOString(),
                    'check_out' => $fixRequest->attendance->check_out?->toISOString(),
                ];
            }

            return $data;
        });
    }

    /**
     * Store a newly created fix request
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        if (!$request->user()->tokenCan('employee')) {
            return $this->forbidden('Only employees can create fix requests');
        }

        $validated = $request->validate([
            'attendance_id' => ['required', 'exists:attendances,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $employeeUser = $request->user();

        // Verify the attendance belongs to the employee
        $attendance = Attendance::find($validated['attendance_id']);
        if ($attendance->employee_user_id !== $employeeUser->id) {
            return $this->forbidden('You can only create fix requests for your own attendance');
        }

        // Check if there's already a pending fix request
        $existingRequest = AttendanceFixRequest::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return $this->error('You already have a pending fix request for this attendance record', 400);
        }

        $fixRequest = AttendanceFixRequest::create([
            'employee_user_id' => $employeeUser->id,
            'attendance_id' => $validated['attendance_id'],
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        $fixRequest->load('attendance');

        return $this->created([
            'id' => $fixRequest->id,
            'employee_user_id' => $fixRequest->employee_user_id,
            'attendance_id' => $fixRequest->attendance_id,
            'reason' => $fixRequest->reason,
            'status' => $fixRequest->status,
            'created_at' => $fixRequest->created_at?->toISOString(),
            'attendance' => [
                'id' => $fixRequest->attendance->id,
                'attendance_date' => $fixRequest->attendance->attendance_date?->toDateString(),
                'check_in' => $fixRequest->attendance->check_in?->toISOString(),
                'check_out' => $fixRequest->attendance->check_out?->toISOString(),
            ],
        ], 'Fix request submitted successfully. An admin will review it soon.');
    }

    /**
     * Display the specified fix request
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        $query = AttendanceFixRequest::with(['employeeUser.employee', 'attendance', 'processedBy']);

        if ($user->tokenCan('admin')) {
            $employeeUserIds = EmployeeUser::where('admin_id', $user->id)->pluck('id');
            $query->whereIn('employee_user_id', $employeeUserIds);
        } elseif ($user->tokenCan('employee')) {
            $query->where('employee_user_id', $user->id);
        } else {
            return $this->forbidden('Invalid token permissions');
        }

        $fixRequest = $query->find($id);

        if (!$fixRequest) {
            return $this->notFound('Fix request not found');
        }

        $data = [
            'id' => $fixRequest->id,
            'employee_user_id' => $fixRequest->employee_user_id,
            'attendance_id' => $fixRequest->attendance_id,
            'reason' => $fixRequest->reason,
            'status' => $fixRequest->status,
            'admin_notes' => $fixRequest->admin_notes,
            'processed_at' => $fixRequest->processed_at?->toISOString(),
            'created_at' => $fixRequest->created_at?->toISOString(),
            'updated_at' => $fixRequest->updated_at?->toISOString(),
        ];

        // Add employee info for admin
        if ($user->tokenCan('admin')) {
            $data['employee'] = [
                'id' => $fixRequest->employeeUser->id,
                'name' => $fixRequest->employeeUser?->employee?->name ?? $fixRequest->employeeUser?->name ?? 'Unknown',
                'email' => $fixRequest->employeeUser?->employee?->email ?? $fixRequest->employeeUser?->email,
            ];
        }

        // Add processor info if processed
        if ($fixRequest->processed_at && $fixRequest->processedBy) {
            $data['processed_by'] = [
                'id' => $fixRequest->processedBy->id,
                'name' => $fixRequest->processedBy->name,
                'email' => $fixRequest->processedBy->email,
            ];
        }

        // Add attendance details
        if ($fixRequest->attendance) {
            $data['attendance'] = [
                'id' => $fixRequest->attendance->id,
                'attendance_date' => $fixRequest->attendance->attendance_date?->toDateString(),
                'check_in' => $fixRequest->attendance->check_in?->toISOString(),
                'check_in_latitude' => $fixRequest->attendance->check_in_latitude,
                'check_in_longitude' => $fixRequest->attendance->check_in_longitude,
                'check_in_distance_meters' => $fixRequest->attendance->check_in_distance_meters,
                'check_out' => $fixRequest->attendance->check_out?->toISOString(),
                'check_out_latitude' => $fixRequest->attendance->check_out_latitude,
                'check_out_longitude' => $fixRequest->attendance->check_out_longitude,
                'check_out_distance_meters' => $fixRequest->attendance->check_out_distance_meters,
                'work_duration' => $fixRequest->attendance->work_duration,
                'formatted_work_duration' => $fixRequest->attendance->formatted_work_duration,
            ];
        }

        return $this->success($data, 'Fix request retrieved successfully');
    }

    /**
     * Process (approve/reject) the fix request
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function process(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can process fix requests');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $adminId = $request->user()->id;
        $employeeUserIds = EmployeeUser::where('admin_id', $adminId)->pluck('id');

        $fixRequest = AttendanceFixRequest::whereIn('employee_user_id', $employeeUserIds)
            ->find($id);

        if (!$fixRequest) {
            return $this->notFound('Fix request not found');
        }

        if (!$fixRequest->isPending()) {
            return $this->error('This fix request has already been processed', 400);
        }

        $fixRequest->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'processed_by' => $adminId,
            'processed_at' => Carbon::now(),
        ]);

        $fixRequest->load('processedBy');

        $statusText = $validated['status'] === 'approved' ? 'approved' : 'rejected';

        return $this->success([
            'id' => $fixRequest->id,
            'status' => $fixRequest->status,
            'admin_notes' => $fixRequest->admin_notes,
            'processed_at' => $fixRequest->processed_at?->toISOString(),
            'processed_by' => [
                'id' => $fixRequest->processedBy->id,
                'name' => $fixRequest->processedBy->name,
            ],
        ], "Fix request has been {$statusText} successfully");
    }
}
