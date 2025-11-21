<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\EmployeeUser;
use App\Services\LogRetentionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceLogController extends Controller
{
    private LogRetentionService $retentionService;

    public function __construct(LogRetentionService $retentionService)
    {
        $this->retentionService = $retentionService;
    }

    /**
     * Display a listing of attendance logs.
     */
    public function index(Request $request)
    {
        $this->retentionService->pruneAttendanceLogs();

        $cutoff = Carbon::now()->subDays(LogRetentionService::ATTENDANCE_RETENTION_DAYS);

        $query = AttendanceLog::with(['employeeUser.employee', 'attendance'])
            ->where(function ($builder) use ($cutoff) {
                $builder->where('attempted_at', '>=', $cutoff)
                    ->orWhere(function ($inner) use ($cutoff) {
                        $inner->whereNull('attempted_at')
                            ->whereNotNull('created_at')
                            ->where('created_at', '>=', $cutoff);
                    });
            })
            ->orderBy('attempted_at', 'desc');

        // Filter by employee
        if ($request->filled('employee_user_id')) {
            $query->where('employee_user_id', $request->employee_user_id);
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by failure reason
        if ($request->filled('failure_reason')) {
            $query->where('failure_reason', $request->failure_reason);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('attempted_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('attempted_at', '<=', $request->date_to);
        }

        // Filter failed attempts only
        if ($request->has('failed_only') && $request->failed_only) {
            $query->failedAttempts();
        }

        $logs = $query->paginate(10)->withQueryString();
        $employees = EmployeeUser::with('employee')->get();

        return view('admin.attendance-logs.index', [
            'logs' => $logs,
            'employees' => $employees,
            'retentionDays' => LogRetentionService::ATTENDANCE_RETENTION_DAYS,
        ]);
    }

    /**
     * Display the specified attendance log.
     */
    public function show(AttendanceLog $log)
    {
        $log->load(['employeeUser.employee.user', 'attendance']);
        
        return view('admin.attendance-logs.show', compact('log'));
    }

    public function cleanup(Request $request)
    {
        $admin = Auth::guard('web')->user();

        if (!$admin) {
            abort(403, 'Only administrators can clear attendance logs.');
        }

        $deleted = $this->retentionService->clearAttendanceLogs();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'deleted' => $deleted,
                'message' => 'Attendance logs cleared successfully.',
            ]);
        }

        return redirect()->route('admin.attendance-logs.index')
            ->with('success', sprintf('%d attendance log%s removed successfully.', $deleted, $deleted === 1 ? '' : 's'));
    }

}
