<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Traits\ApiPagination;
use App\Http\Traits\ApiFiltering;
use App\Http\Traits\ApiSorting;
use App\Models\EmployeeActivityLog;
use App\Models\EmployeeUser;
use App\Services\LogRetentionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ActivityLogApiController extends BaseApiController
{
    use ApiPagination, ApiFiltering, ApiSorting;

    public function __construct(protected LogRetentionService $retentionService)
    {
    }

    /**
     * Display a listing of activity logs
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can view activity logs');
        }

        $adminId = $request->user()->id;

        // Auto-prune old logs
        $this->retentionService->pruneActivityLogs($adminId);

        $cutoff = Carbon::now()->subDays(LogRetentionService::ACTIVITY_RETENTION_DAYS);

        $query = EmployeeActivityLog::with(['employeeUser.employee'])
            ->where('admin_id', $adminId)
            ->where(function ($builder) use ($cutoff) {
                $builder->where('occurred_at', '>=', $cutoff)
                    ->orWhere(function ($inner) use ($cutoff) {
                        $inner->whereNull('occurred_at')
                            ->whereNotNull('created_at')
                            ->where('created_at', '>=', $cutoff);
                    });
            });

        // Apply filters
        $this->applyFilters($query, [
            'employee_user_id' => '=',
            'category' => '=',
            'action' => '=',
            'request_method' => '=',
            'device_type' => '=',
            'response_status' => '=',
        ]);

        // IP address filter
        if ($request->filled('ip_address')) {
            $ip = $request->ip_address;
            $query->where(function ($inner) use ($ip) {
                $inner->where('ip_address', 'like', "%{$ip}%")
                    ->orWhere('ip_address_v4', 'like', "%{$ip}%")
                    ->orWhere('ip_address_v6', 'like', "%{$ip}%");
            });
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($inner) use ($search) {
                $inner->where('summary', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('request_path', 'like', "%{$search}%")
                    ->orWhere('route_name', 'like', "%{$search}%")
                    ->orWhere('browser', 'like', "%{$search}%")
                    ->orWhere('os', 'like', "%{$search}%");
            });
        }

        // Date range filter
        if ($request->has('date_from')) {
            $query->whereDate('occurred_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('occurred_at', '<=', $request->date_to);
        }

        // Apply sorting
        $this->applySorting($query, [
            'id', 'occurred_at', 'action', 'summary', 'response_status', 'category', 'created_at'
        ], 'occurred_at', 'desc');

        $logs = $this->applyPagination($query);

        return $this->paginated($logs, function ($log) {
            return [
                'id' => $log->id,
                'employee_user_id' => $log->employee_user_id,
                'employee_name' => $log->employee_display_name,
                'category' => $log->category,
                'action' => $log->action,
                'summary' => $log->summary,
                'description' => $log->description,
                'request_method' => $log->request_method,
                'route_name' => $log->route_name,
                'request_path' => $log->request_path,
                'response_status' => $log->response_status,
                'ip_address' => $log->ip_address,
                'ip_address_v4' => $log->ip_address_v4,
                'ip_address_v6' => $log->ip_address_v6,
                'device_type' => $log->device_type,
                'browser' => $log->browser,
                'os' => $log->os,
                'occurred_at' => $log->occurred_at?->toISOString(),
                'created_at' => $log->created_at?->toISOString(),
            ];
        });
    }

    /**
     * Display the specified activity log
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can view activity logs');
        }

        $adminId = $request->user()->id;

        $log = EmployeeActivityLog::with(['employeeUser.employee', 'admin'])
            ->where('admin_id', $adminId)
            ->find($id);

        if (!$log) {
            return $this->notFound('Activity log not found');
        }

        return $this->success([
            'id' => $log->id,
            'employee_user_id' => $log->employee_user_id,
            'employee_name' => $log->employee_display_name,
            'category' => $log->category,
            'action' => $log->action,
            'summary' => $log->summary,
            'description' => $log->description,
            'request_method' => $log->request_method,
            'route_name' => $log->route_name,
            'request_path' => $log->request_path,
            'referer' => $log->referer,
            'response_status' => $log->response_status,
            'ip_address' => $log->ip_address,
            'ip_address_v4' => $log->ip_address_v4,
            'ip_address_v6' => $log->ip_address_v6,
            'device_type' => $log->device_type,
            'browser' => $log->browser,
            'os' => $log->os,
            'user_agent' => $log->user_agent,
            'request_payload' => $log->request_payload,
            'metadata' => $log->metadata,
            'occurred_at' => $log->occurred_at?->toISOString(),
            'created_at' => $log->created_at?->toISOString(),
            'updated_at' => $log->updated_at?->toISOString(),
        ], 'Activity log retrieved successfully');
    }

    /**
     * Clear old activity logs
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cleanup(Request $request)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can clear activity logs');
        }

        $adminId = $request->user()->id;
        $deleted = $this->retentionService->clearActivityLogsForAdmin($adminId);

        return $this->success([
            'deleted' => $deleted,
        ], sprintf('%d activity log%s removed successfully', $deleted, $deleted === 1 ? '' : 's'));
    }
}
