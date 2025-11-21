<?php

namespace App\Http\Controllers;

use App\Models\EmployeeActivityLog;
use App\Models\EmployeeUser;
use App\Services\LogRetentionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class EmployeeActivityLogController extends Controller
{
    public function __construct(private readonly LogRetentionService $retentionService)
    {
    }

    /**
     * Display a listing of employee activity logs.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', EmployeeActivityLog::class);

        $admin = auth()->guard('web')->user();
        $adminId = $admin?->id;

        if (!$adminId) {
            abort(403, 'Only administrators can view employee activity logs.');
        }

        $this->retentionService->pruneActivityLogs($adminId);

        $cutoff = Carbon::now()->subDays(LogRetentionService::ACTIVITY_RETENTION_DAYS);

        $perPageInput = $request->input('per_page');
        $perPage = max(min((int) ($perPageInput ?: 25), 100), 5);
        $sort = $request->get('sort', 'occurred_at');
        $directionInput = strtolower($request->get('direction', 'desc'));
        $direction = $directionInput === 'asc' ? 'asc' : 'desc';

        $allowedSorts = [
            'occurred_at' => 'employee_activity_logs.occurred_at',
            'action' => 'employee_activity_logs.action',
            'summary' => 'employee_activity_logs.summary',
            'response_status' => 'employee_activity_logs.response_status',
        ];

        $query = EmployeeActivityLog::query()
            ->with(['employeeUser.employee'])
            ->where('admin_id', $adminId)
            ->where(function ($builder) use ($cutoff) {
                $builder->where('occurred_at', '>=', $cutoff)
                    ->orWhere(function ($inner) use ($cutoff) {
                        $inner->whereNull('occurred_at')
                            ->whereNotNull('created_at')
                            ->where('created_at', '>=', $cutoff);
                    });
            });

        // Filters
        if ($request->filled('employee_user_id')) {
            $query->where('employee_user_id', $request->integer('employee_user_id'));
        }

        if ($request->filled('http_method')) {
            $query->where('request_method', strtoupper($request->string('http_method')));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        if ($request->filled('action_filter')) {
            $query->where('action', $request->string('action_filter'));
        }

        if ($request->filled('ip_address')) {
            $ip = $request->string('ip_address');
            $query->where(function ($inner) use ($ip) {
                $inner->where('ip_address', 'like', "%{$ip}%")
                    ->orWhere('ip_address_v4', 'like', "%{$ip}%")
                    ->orWhere('ip_address_v6', 'like', "%{$ip}%");
            });
        }

        if ($request->filled('device_type')) {
            $query->where('device_type', $request->string('device_type'));
        }

        if ($request->filled('response_status')) {
            $query->where('response_status', $request->integer('response_status'));
        }

        $search = trim((string) $request->get('search', ''));
        if ($search !== '') {
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

        $dateFrom = $this->parseDate($request->input('date_from'));
        $dateTo = $this->parseDate($request->input('date_to'));

        if ($dateFrom && $dateTo && $dateFrom->greaterThan($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        if ($dateFrom) {
            $query->whereDate('occurred_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('occurred_at', '<=', $dateTo);
        }

        if ($request->filled('response_status')) {
            $query->where('response_status', $request->integer('response_status'));
        }

        $sortColumn = $allowedSorts[$sort] ?? $allowedSorts['occurred_at'];

        if ($sort === 'employee') {
            $query->leftJoin('employee_users', 'employee_activity_logs.employee_user_id', '=', 'employee_users.id')
                ->leftJoin('employees', 'employee_users.employee_id', '=', 'employees.id')
                ->select('employee_activity_logs.*')
                ->orderByRaw('COALESCE(employees.name, employee_users.name, ?) ' . $direction, ['Unknown']);
        } else {
            $query->orderBy($sortColumn, $direction);
        }

        $logs = $query->paginate($perPage)->withQueryString();

        $employees = EmployeeUser::with('employee')
            ->where('admin_id', $adminId)
            ->orderBy('name')
            ->get();

        $categories = EmployeeActivityLog::where('admin_id', $adminId)
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $actions = EmployeeActivityLog::where('admin_id', $adminId)
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $methods = EmployeeActivityLog::where('admin_id', $adminId)
            ->whereNotNull('request_method')
            ->distinct()
            ->pluck('request_method')
            ->map(fn ($method) => strtoupper($method))
            ->sort()
            ->values();

        $deviceTypes = EmployeeActivityLog::where('admin_id', $adminId)
            ->whereNotNull('device_type')
            ->distinct()
            ->orderBy('device_type')
            ->pluck('device_type');

        $responseStatuses = EmployeeActivityLog::where('admin_id', $adminId)
            ->whereNotNull('response_status')
            ->distinct()
            ->orderBy('response_status')
            ->pluck('response_status');

        if ($request->wantsJson()) {
            return response()->json([
                'data' => $logs->items(),
                'meta' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                ],
            ]);
        }

        return view('admin.activity-logs.index', [
            'logs' => $logs,
            'employees' => $employees,
            'categories' => $categories,
            'actions' => $actions,
            'methods' => $methods,
            'deviceTypes' => $deviceTypes,
            'responseStatuses' => $responseStatuses,
            'filters' => [
                'employee_user_id' => $request->input('employee_user_id'),
                'category' => $request->input('category'),
                'action_filter' => $request->input('action_filter'),
                'ip_address' => $request->input('ip_address'),
                'device_type' => $request->input('device_type'),
                'search' => $search,
                'date_from' => optional($dateFrom)->format('Y-m-d'),
                'date_to' => optional($dateTo)->format('Y-m-d'),
                'response_status' => $request->input('response_status'),
                'per_page' => $perPageInput ?: $perPage,
                'sort' => $sort,
                'direction' => $direction,
                'http_method' => $request->input('http_method'),
            ],
            'retentionDays' => LogRetentionService::ACTIVITY_RETENTION_DAYS,
        ]);
    }

    /**
     * Display a single log entry.
     */
    public function show(EmployeeActivityLog $log)
    {
        Gate::authorize('view', $log);

        $admin = auth()->guard('web')->user();

        if (!$admin || $log->admin_id !== $admin->id) {
            abort(403);
        }

        $log->load(['employeeUser.employee', 'admin']);

        return view('admin.activity-logs.show', compact('log'));
    }

    public function cleanup(Request $request)
    {
        Gate::authorize('viewAny', EmployeeActivityLog::class);

        $admin = Auth::guard('web')->user();

        if (!$admin) {
            abort(403, 'Only administrators can clear activity logs.');
        }

        $deleted = $this->retentionService->clearActivityLogsForAdmin($admin->id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'deleted' => $deleted,
                'message' => 'Activity logs cleared successfully.',
            ]);
        }

        return redirect()->route('admin.activity-logs.index')
            ->with('success', sprintf('%d activity log%s removed successfully.', $deleted, $deleted === 1 ? '' : 's'));
    }

    private function parseDate($value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', (string) $value);
        } catch (\Throwable $e) {
            return null;
        }
    }

}
