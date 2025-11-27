<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Traits\ApiPagination;
use App\Http\Traits\ApiFiltering;
use App\Http\Traits\ApiSorting;
use App\Models\AttendanceLog;
use App\Models\EmployeeUser;
use App\Services\LogRetentionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceLogApiController extends BaseApiController
{
    use ApiPagination, ApiFiltering, ApiSorting;

    public function __construct(protected LogRetentionService $retentionService)
    {
    }

    /**
     * Display a listing of attendance logs
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can view attendance logs');
        }

        // Auto-prune old logs
        $this->retentionService->pruneAttendanceLogs();

        $cutoff = Carbon::now()->subDays(LogRetentionService::ATTENDANCE_RETENTION_DAYS);
        $adminId = $request->user()->id;

        // Get employee user IDs for this admin
        $employeeUserIds = EmployeeUser::where('admin_id', $adminId)->pluck('id');

        $query = AttendanceLog::with(['employeeUser.employee', 'attendance'])
            ->whereIn('employee_user_id', $employeeUserIds)
            ->where(function ($builder) use ($cutoff) {
                $builder->where('attempted_at', '>=', $cutoff)
                    ->orWhere(function ($inner) use ($cutoff) {
                        $inner->whereNull('attempted_at')
                            ->whereNotNull('created_at')
                            ->where('created_at', '>=', $cutoff);
                    });
            });

        // Apply filters
        $this->applyFilters($query, [
            'employee_user_id' => '=',
            'action' => '=',
            'failure_reason' => '=',
        ]);

        // Date range filter
        if ($request->has('date_from')) {
            $query->whereDate('attempted_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('attempted_at', '<=', $request->date_to);
        }

        // Filter failed attempts only
        if ($request->has('failed_only') && $request->failed_only) {
            $query->failedAttempts();
        }

        // Filter successful attempts only
        if ($request->has('successful_only') && $request->successful_only) {
            $query->successfulAttempts();
        }

        // Apply sorting
        $this->applySorting($query, [
            'id', 'attempted_at', 'action', 'failure_reason', 'distance_from_office', 'created_at'
        ], 'attempted_at', 'desc');

        $logs = $this->applyPagination($query);

        return $this->paginated($logs, function ($log) {
            return [
                'id' => $log->id,
                'employee_user_id' => $log->employee_user_id,
                'employee_name' => $log->employeeUser?->employee?->name ?? $log->employeeUser?->name ?? 'Unknown',
                'attendance_id' => $log->attendance_id,
                'action' => $log->action,
                'failure_reason' => $log->failure_reason,
                'latitude' => $log->latitude,
                'longitude' => $log->longitude,
                'distance_from_office' => $log->distance_from_office,
                'ip_address' => $log->ip_address,
                'ip_address_v4' => $log->ip_address_v4,
                'ip_address_v6' => $log->ip_address_v6,
                'user_agent' => $log->user_agent,
                'device_type' => $log->device_type,
                'browser' => $log->browser,
                'os' => $log->os,
                'additional_info' => $log->additional_info,
                'attempted_at' => $log->attempted_at?->toISOString(),
                'created_at' => $log->created_at?->toISOString(),
            ];
        });
    }

    /**
     * Display the specified attendance log
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can view attendance logs');
        }

        $adminId = $request->user()->id;
        $employeeUserIds = EmployeeUser::where('admin_id', $adminId)->pluck('id');

        $log = AttendanceLog::with(['employeeUser.employee.user', 'attendance'])
            ->whereIn('employee_user_id', $employeeUserIds)
            ->find($id);

        if (!$log) {
            return $this->notFound('Attendance log not found');
        }

        return $this->success([
            'id' => $log->id,
            'employee_user_id' => $log->employee_user_id,
            'employee_name' => $log->employeeUser?->employee?->name ?? $log->employeeUser?->name ?? 'Unknown',
            'attendance_id' => $log->attendance_id,
            'action' => $log->action,
            'failure_reason' => $log->failure_reason,
            'latitude' => $log->latitude,
            'longitude' => $log->longitude,
            'distance_from_office' => $log->distance_from_office,
            'formatted_location' => $log->formatted_location,
            'formatted_distance' => $log->formatted_distance,
            'ip_address' => $log->ip_address,
            'ip_address_v4' => $log->ip_address_v4,
            'ip_address_v6' => $log->ip_address_v6,
            'user_agent' => $log->user_agent,
            'device_type' => $log->device_type,
            'browser' => $log->browser,
            'os' => $log->os,
            'additional_info' => $log->additional_info,
            'attempted_at' => $log->attempted_at?->toISOString(),
            'created_at' => $log->created_at?->toISOString(),
            'updated_at' => $log->updated_at?->toISOString(),
            'attendance' => $log->attendance ? [
                'id' => $log->attendance->id,
                'attendance_date' => $log->attendance->attendance_date?->toDateString(),
                'check_in' => $log->attendance->check_in?->toISOString(),
                'check_out' => $log->attendance->check_out?->toISOString(),
            ] : null,
        ], 'Attendance log retrieved successfully');
    }

    /**
     * Clear old attendance logs
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cleanup(Request $request)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can clear attendance logs');
        }

        $deleted = $this->retentionService->clearAttendanceLogs();

        return $this->success([
            'deleted' => $deleted,
        ], sprintf('%d attendance log%s removed successfully', $deleted, $deleted === 1 ? '' : 's'));
    }
}
