<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Services\GeolocationService;
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
    public function checkIn(Request $request, GeolocationService $geoService)
    {
        $employeeUser = Auth::guard('employee')->user();
        $today = Carbon::today();
        $user = $employeeUser->employee->user;

        // Validate request data
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $latitude = $validated['latitude'];
        $longitude = $validated['longitude'];

        // Log for debugging
        \Log::info('Check-in attempt', [
            'employee_id' => $employeeUser->id,
            'employee_coords' => ['lat' => $latitude, 'lon' => $longitude],
            'office_coords' => ['lat' => $user->office_latitude, 'lon' => $user->office_longitude],
        ]);

        // Check if already checked in today
        $existingAttendance = Attendance::forEmployee($employeeUser->id)
            ->where('attendance_date', $today)
            ->first();

        if ($existingAttendance) {
            // Log failed attempt
            $geoService->logAttempt(
                $employeeUser->id,
                $existingAttendance->id,
                'check_in_failed',
                'already_checked_in',
                $latitude,
                $longitude,
                null,
                $request
            );

            return response()->json([
                'success' => false,
                'message' => 'You have already checked in today.',
            ], 400);
        }

        // Check if office location is configured
        if (!$user->office_latitude || !$user->office_longitude) {
            // Log attempt
            $geoService->logAttempt(
                $employeeUser->id,
                null,
                'check_in_failed',
                'other',
                $latitude,
                $longitude,
                null,
                $request,
                ['error' => 'Office location not configured']
            );

            return response()->json([
                'success' => false,
                'message' => 'Office location is not configured. Please contact your administrator.',
            ], 400);
        }

        // Calculate distance from office
        $distance = $geoService->calculateDistance(
            $latitude,
            $longitude,
            $user->office_latitude,
            $user->office_longitude
        );

        $radiusMeters = $user->office_radius_meters ?? 15;

        // Log distance calculation for debugging
        \Log::info('Check-in distance calculated', [
            'employee_id' => $employeeUser->id,
            'distance_meters' => round($distance, 2),
            'allowed_radius' => $radiusMeters,
            'within_range' => $distance <= $radiusMeters,
            'employee_lat' => $latitude,
            'employee_lon' => $longitude,
            'office_lat' => $user->office_latitude,
            'office_lon' => $user->office_longitude,
        ]);

        // Check if within allowed radius
        if ($distance > $radiusMeters) {
            // Log failed attempt
            $geoService->logAttempt(
                $employeeUser->id,
                null,
                'check_in_failed',
                'out_of_range',
                $latitude,
                $longitude,
                $distance,
                $request
            );

            return response()->json([
                'success' => false,
                'message' => "You are too far from the office. You must be within {$radiusMeters} meters to check in. Current distance: " . number_format($distance, 2) . " meters.",
                'distance' => round($distance, 2),
                'required_distance' => $radiusMeters,
            ], 403);
        }

        // Create new attendance record with check-in time and location
        $attendance = Attendance::create([
            'employee_user_id' => $employeeUser->id,
            'attendance_date' => $today,
            'check_in' => Carbon::now(),
            'check_in_latitude' => $latitude,
            'check_in_longitude' => $longitude,
            'check_in_ip' => $geoService->getClientIp($request),
            'check_in_user_agent' => $request->userAgent(),
            'check_in_distance_meters' => $distance,
        ]);

        // Log successful check-in
        $geoService->logAttempt(
            $employeeUser->id,
            $attendance->id,
            'check_in_success',
            null,
            $latitude,
            $longitude,
            $distance,
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Successfully checked in at ' . $attendance->check_in->format('h:i A'),
            'check_in_time' => $attendance->check_in->format('h:i A'),
        ]);
    }

    /**
     * Check out the employee.
     */
    public function checkOut(Request $request, GeolocationService $geoService)
    {
        $employeeUser = Auth::guard('employee')->user();
        $today = Carbon::today();
        $user = $employeeUser->employee->user;

        // Validate request data
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $latitude = $validated['latitude'];
        $longitude = $validated['longitude'];

        // Find today's attendance
        $attendance = Attendance::forEmployee($employeeUser->id)
            ->where('attendance_date', $today)
            ->first();

        if (!$attendance) {
            $geoService->logAttempt(
                $employeeUser->id,
                null,
                'check_out_failed',
                'not_checked_in',
                $latitude,
                $longitude,
                null,
                $request
            );

            return response()->json([
                'success' => false,
                'message' => 'You need to check in first.',
            ], 400);
        }

        if (!$attendance->hasCheckedIn()) {
            $geoService->logAttempt(
                $employeeUser->id,
                $attendance->id,
                'check_out_failed',
                'not_checked_in',
                $latitude,
                $longitude,
                null,
                $request
            );

            return response()->json([
                'success' => false,
                'message' => 'You need to check in first.',
            ], 400);
        }

        if ($attendance->hasCheckedOut()) {
            $geoService->logAttempt(
                $employeeUser->id,
                $attendance->id,
                'check_out_failed',
                'already_checked_out',
                $latitude,
                $longitude,
                null,
                $request
            );

            return response()->json([
                'success' => false,
                'message' => 'You have already checked out today.',
            ], 400);
        }

        // Check if office location is configured
        if (!$user->office_latitude || !$user->office_longitude) {
            $geoService->logAttempt(
                $employeeUser->id,
                $attendance->id,
                'check_out_failed',
                'other',
                $latitude,
                $longitude,
                null,
                $request,
                ['error' => 'Office location not configured']
            );

            return response()->json([
                'success' => false,
                'message' => 'Office location is not configured. Please contact your administrator.',
            ], 400);
        }

        // Calculate distance from office
        $distance = $geoService->calculateDistance(
            $latitude,
            $longitude,
            $user->office_latitude,
            $user->office_longitude
        );

        $radiusMeters = $user->office_radius_meters ?? 15;

        // Check if within allowed radius
        if ($distance > $radiusMeters) {
            $geoService->logAttempt(
                $employeeUser->id,
                $attendance->id,
                'check_out_failed',
                'out_of_range',
                $latitude,
                $longitude,
                $distance,
                $request
            );

            return response()->json([
                'success' => false,
                'message' => "You are too far from the office. You must be within {$radiusMeters} meters to check out. Current distance: " . number_format($distance, 2) . " meters.",
                'distance' => round($distance, 2),
                'required_distance' => $radiusMeters,
            ], 403);
        }

        // Update attendance with check-out time and location
        $attendance->update([
            'check_out' => Carbon::now(),
            'check_out_latitude' => $latitude,
            'check_out_longitude' => $longitude,
            'check_out_ip' => $geoService->getClientIp($request),
            'check_out_user_agent' => $request->userAgent(),
            'check_out_distance_meters' => $distance,
        ]);

        // Log successful check-out
        $geoService->logAttempt(
            $employeeUser->id,
            $attendance->id,
            'check_out_success',
            null,
            $latitude,
            $longitude,
            $distance,
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Successfully checked out at ' . $attendance->check_out->format('h:i A'),
            'check_out_time' => $attendance->check_out->format('h:i A'),
        ]);
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
