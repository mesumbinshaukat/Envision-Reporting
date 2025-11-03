<?php

namespace App\Http\Controllers;

use App\Models\AttendanceFixRequest;
use App\Models\Attendance;
use App\Http\Requests\StoreAttendanceFixRequestRequest;
use App\Http\Requests\ProcessAttendanceFixRequestRequest;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceFixRequestController extends Controller
{
    /**
     * Display a listing of the employee's fix requests.
     */
    public function index()
    {
        $employeeUser = Auth::guard('employee')->user();

        $fixRequests = AttendanceFixRequest::forEmployee($employeeUser->id)
            ->with('attendance', 'processedBy')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('attendance.fix-requests.index', compact('fixRequests'));
    }

    /**
     * Show the form for creating a new fix request.
     */
    public function create(Attendance $attendance)
    {
        $employeeUser = Auth::guard('employee')->user();

        // Ensure employee can only create fix requests for their own attendance
        if ($attendance->employee_user_id !== $employeeUser->id) {
            abort(403, 'Unauthorized action.');
        }

        // Check if there's already a pending fix request for this attendance
        $existingRequest = AttendanceFixRequest::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return redirect()->route('attendance.index')
                ->with('error', 'You already have a pending fix request for this attendance record.');
        }

        return view('attendance.fix-requests.create', compact('attendance'));
    }

    /**
     * Store a newly created fix request in storage.
     */
    public function store(StoreAttendanceFixRequestRequest $request)
    {
        $employeeUser = Auth::guard('employee')->user();
        $validated = $request->validated();

        // Verify the attendance belongs to the employee
        $attendance = Attendance::findOrFail($validated['attendance_id']);
        if ($attendance->employee_user_id !== $employeeUser->id) {
            abort(403, 'Unauthorized action.');
        }

        // Check if there's already a pending fix request
        $existingRequest = AttendanceFixRequest::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return redirect()->route('attendance.index')
                ->with('error', 'You already have a pending fix request for this attendance record.');
        }

        AttendanceFixRequest::create([
            'employee_user_id' => $employeeUser->id,
            'attendance_id' => $validated['attendance_id'],
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return redirect()->route('attendance.fix-requests.index')
            ->with('success', 'Fix request submitted successfully. An admin will review it soon.');
    }

    /**
     * Display the specified fix request.
     */
    public function show(AttendanceFixRequest $fixRequest)
    {
        $employeeUser = Auth::guard('employee')->user();

        // Ensure employee can only view their own fix requests
        if ($fixRequest->employee_user_id !== $employeeUser->id) {
            abort(403, 'Unauthorized action.');
        }

        $fixRequest->load('attendance', 'processedBy');

        return view('attendance.fix-requests.show', compact('fixRequest'));
    }

    /**
     * Display a listing of all fix requests for admin.
     */
    public function adminIndex()
    {
        $fixRequests = AttendanceFixRequest::with('employeeUser', 'attendance', 'processedBy')
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.attendance.fix-requests.index', compact('fixRequests'));
    }

    /**
     * Display the specified fix request for admin.
     */
    public function adminShow(AttendanceFixRequest $fixRequest)
    {
        $fixRequest->load('employeeUser', 'attendance', 'processedBy');
        return view('admin.attendance.fix-requests.show', compact('fixRequest'));
    }

    /**
     * Process (approve/reject) the fix request.
     */
    public function process(ProcessAttendanceFixRequestRequest $request, AttendanceFixRequest $fixRequest)
    {
        $validated = $request->validated();

        if (!$fixRequest->isPending()) {
            return redirect()->route('admin.attendance.fix-requests.index')
                ->with('error', 'This fix request has already been processed.');
        }

        $fixRequest->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'processed_by' => Auth::guard('web')->id(),
            'processed_at' => Carbon::now(),
        ]);

        $statusText = $validated['status'] === 'approved' ? 'approved' : 'rejected';

        return redirect()->route('admin.attendance.fix-requests.index')
            ->with('success', "Fix request has been {$statusText} successfully.");
    }

    /**
     * Show the form for editing the attendance based on approved fix request.
     */
    public function editAttendance(AttendanceFixRequest $fixRequest)
    {
        if (!$fixRequest->isApproved()) {
            return redirect()->route('admin.attendance.fix-requests.index')
                ->with('error', 'Only approved fix requests can be used to edit attendance.');
        }

        $attendance = $fixRequest->attendance;
        return redirect()->route('admin.attendance.edit', $attendance);
    }
}
