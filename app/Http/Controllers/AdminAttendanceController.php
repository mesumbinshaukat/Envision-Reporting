<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\EmployeeUser;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminAttendanceController extends Controller
{
    /**
     * Display a listing of all attendance records with filters.
     */
    public function index(Request $request)
    {
        $query = Attendance::with('employeeUser');

        // Filter by employee
        if ($request->filled('employee_user_id')) {
            $query->where('employee_user_id', $request->employee_user_id);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        } elseif ($request->filled('start_date')) {
            $query->where('attendance_date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->where('attendance_date', '<=', $request->end_date);
        }

        // Filter by status
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'checked_in':
                    $query->checkedInOnly();
                    break;
                case 'checked_out':
                    $query->completed();
                    break;
                case 'missing':
                    $query->whereNull('check_in');
                    break;
            }
        }

        // Order by date descending
        $attendances = $query->orderBy('attendance_date', 'desc')
            ->orderBy('check_in', 'desc')
            ->paginate(20);

        // Get all employees for filter dropdown
        $employees = EmployeeUser::orderBy('name')->get();

        return view('admin.attendance.index', compact('attendances', 'employees'));
    }

    /**
     * Show the form for creating a new attendance record.
     */
    public function create()
    {
        $employees = EmployeeUser::orderBy('name')->get();
        return view('admin.attendance.create', compact('employees'));
    }

    /**
     * Store a newly created attendance record in storage.
     */
    public function store(StoreAttendanceRequest $request)
    {
        $validated = $request->validated();

        // Check if attendance already exists for this employee on this date
        $existingAttendance = Attendance::where('employee_user_id', $validated['employee_user_id'])
            ->where('attendance_date', $validated['attendance_date'])
            ->first();

        if ($existingAttendance) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Attendance record already exists for this employee on this date.');
        }

        Attendance::create($validated);

        return redirect()->route('admin.attendance.index')
            ->with('success', 'Attendance record created successfully.');
    }

    /**
     * Display the specified attendance record.
     */
    public function show(Attendance $attendance)
    {
        $attendance->load('employeeUser', 'fixRequests.processedBy');
        return view('admin.attendance.show', compact('attendance'));
    }

    /**
     * Show the form for editing the specified attendance record.
     */
    public function edit(Attendance $attendance)
    {
        $employees = EmployeeUser::orderBy('name')->get();
        return view('admin.attendance.edit', compact('attendance', 'employees'));
    }

    /**
     * Update the specified attendance record in storage.
     */
    public function update(UpdateAttendanceRequest $request, Attendance $attendance)
    {
        $validated = $request->validated();
        $attendance->update($validated);

        return redirect()->route('admin.attendance.index')
            ->with('success', 'Attendance record updated successfully.');
    }

    /**
     * Remove the specified attendance record from storage.
     */
    public function destroy(Attendance $attendance)
    {
        $attendance->delete();

        return redirect()->route('admin.attendance.index')
            ->with('success', 'Attendance record deleted successfully.');
    }

    /**
     * Display attendance statistics and summary.
     */
    public function statistics(Request $request)
    {
        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date) 
            : Carbon::now()->startOfMonth();
        
        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->end_date) 
            : Carbon::now()->endOfMonth();

        // Calculate total working days (excluding Saturdays and Sundays)
        $totalWorkingDays = 0;
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            if (!$currentDate->isWeekend()) {
                $totalWorkingDays++;
            }
            $currentDate->addDay();
        }

        // Get employees query
        $employeesQuery = EmployeeUser::with(['attendances' => function ($query) use ($startDate, $endDate) {
            $query->dateRange($startDate, $endDate);
        }]);

        // Filter by employee if specified
        if ($request->filled('employee_user_id')) {
            $employeesQuery->where('id', $request->employee_user_id);
        }

        $employees = $employeesQuery->get();
        
        // Get all employees for filter dropdown
        $allEmployees = EmployeeUser::orderBy('name')->get();

        // Calculate statistics
        $statistics = $employees->map(function ($employee) use ($startDate, $endDate, $totalWorkingDays) {
            $attendances = $employee->attendances;
            $totalDays = $attendances->count();
            $completedDays = $attendances->filter(fn($a) => $a->hasCheckedOut())->count();
            $totalHours = $attendances->sum('work_duration');
            
            // Calculate days on leave (working days - days with attendance)
            $daysOnLeave = $totalWorkingDays - $totalDays;

            return [
                'employee' => $employee,
                'total_days' => $totalDays,
                'completed_days' => $completedDays,
                'incomplete_days' => $totalDays - $completedDays,
                'total_hours' => round($totalHours, 2),
                'average_hours' => $completedDays > 0 ? round($totalHours / $completedDays, 2) : 0,
                'days_on_leave' => $daysOnLeave,
                'total_working_days' => $totalWorkingDays,
            ];
        });

        return view('admin.attendance.statistics', compact('statistics', 'startDate', 'endDate', 'allEmployees'));
    }
}
