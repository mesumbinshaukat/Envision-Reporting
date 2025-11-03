<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the employee's attendance for the current month.
     */
    public function index()
    {
        // Get the authenticated employee user
        $employeeUser = Auth::guard('employee')->user();
        
        // If not authenticated as employee, redirect to login
        if (!$employeeUser) {
            return redirect()->route('login')->with('error', 'Please login as an employee to access attendance.');
        }

        $attendances = Attendance::forEmployee($employeeUser->id)
            ->currentMonth()
            ->orderBy('attendance_date', 'desc')
            ->with('fixRequests')
            ->get();

        // Get today's attendance if exists
        $todayAttendance = Attendance::forEmployee($employeeUser->id)
            ->where('attendance_date', Carbon::today())
            ->first();

        return view('attendance.index', compact('attendances', 'todayAttendance'));
    }

    /**
     * Check in the employee.
     */
    public function checkIn(Request $request)
    {
        $employeeUser = Auth::guard('employee')->user();
        $today = Carbon::today();

        // Check if already checked in today
        $existingAttendance = Attendance::forEmployee($employeeUser->id)
            ->where('attendance_date', $today)
            ->first();

        if ($existingAttendance) {
            return redirect()->route('attendance.index')
                ->with('error', 'You have already checked in today.');
        }

        // Create new attendance record with check-in time
        $attendance = Attendance::create([
            'employee_user_id' => $employeeUser->id,
            'attendance_date' => $today,
            'check_in' => Carbon::now(),
        ]);

        return redirect()->route('attendance.index')
            ->with('success', 'Successfully checked in at ' . $attendance->check_in->format('h:i A'));
    }

    /**
     * Check out the employee.
     */
    public function checkOut(Request $request)
    {
        $employeeUser = Auth::guard('employee')->user();
        $today = Carbon::today();

        // Find today's attendance
        $attendance = Attendance::forEmployee($employeeUser->id)
            ->where('attendance_date', $today)
            ->first();

        if (!$attendance) {
            return redirect()->route('attendance.index')
                ->with('error', 'You need to check in first.');
        }

        if (!$attendance->hasCheckedIn()) {
            return redirect()->route('attendance.index')
                ->with('error', 'You need to check in first.');
        }

        if ($attendance->hasCheckedOut()) {
            return redirect()->route('attendance.index')
                ->with('error', 'You have already checked out today.');
        }

        // Update attendance with check-out time
        $attendance->update([
            'check_out' => Carbon::now(),
        ]);

        return redirect()->route('attendance.index')
            ->with('success', 'Successfully checked out at ' . $attendance->check_out->format('h:i A'));
    }

    /**
     * Display the specified attendance record.
     */
    public function show(Attendance $attendance)
    {
        $employeeUser = Auth::guard('employee')->user();

        // Ensure employee can only view their own attendance
        if ($attendance->employee_user_id !== $employeeUser->id) {
            abort(403, 'Unauthorized action.');
        }

        $attendance->load('fixRequests.processedBy');

        return view('attendance.show', compact('attendance'));
    }
}
