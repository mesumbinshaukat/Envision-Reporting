<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\EmployeeUser;
use Illuminate\Http\Request;

class AttendanceLogController extends Controller
{
    /**
     * Display a listing of attendance logs.
     */
    public function index(Request $request)
    {
        $query = AttendanceLog::with(['employeeUser.employee', 'attendance'])
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

        $logs = $query->paginate(50);
        $employees = EmployeeUser::with('employee')->get();

        return view('admin.attendance-logs.index', compact('logs', 'employees'));
    }

    /**
     * Display the specified attendance log.
     */
    public function show(AttendanceLog $log)
    {
        $log->load(['employeeUser.employee', 'attendance']);
        
        return view('admin.attendance-logs.show', compact('log'));
    }
}
