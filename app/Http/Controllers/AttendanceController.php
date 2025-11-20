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

        // Always work with a fresh employee relation to reflect latest admin updates
        $employee = $employeeUser->employee()->with('user')->firstOrFail();

        $attendances = Attendance::forEmployee($employeeUser->id)
            ->currentMonth()
            ->orderBy('attendance_date', 'desc')
            ->with('fixRequests')
            ->get();

        // Get today's attendance if exists
        $today = Carbon::today();
        $todayAttendance = Attendance::forEmployee($employeeUser->id)
            ->where('attendance_date', $today)
            ->first();

        if (!$todayAttendance) {
            $todayAttendance = Attendance::forEmployee($employeeUser->id)
                ->whereNull('check_out')
                ->orderByDesc('attendance_date')
                ->orderByDesc('check_in')
                ->first();

            if ($todayAttendance && $todayAttendance->attendance_date->diffInDays($today) > 1) {
                $todayAttendance = null;
            }
        }

        return view('attendance.index', compact('attendances', 'todayAttendance', 'employee'));
    }

    /**
     * Check in the employee.
     */
    public function checkIn(Request $request, GeolocationService $geoService)
    {
        $employeeUser = Auth::guard('employee')->user();
        $today = Carbon::today();
        $employee = $employeeUser->employee()->with(['user', 'ipWhitelists'])->firstOrFail();
        $user = $employee->user;

        $geolocationRequired = $employee->requiresGeolocation();
        $enforceOfficeRadius = $employee->enforcesOfficeRadius();
        $officeEnforcementEnabled = $employee->shouldEnforceOfficeLocation();
        $usesWhitelistMode = $employee->usesWhitelistOverride();

        $ipPair = $geoService->getClientIpPair($request);
        $ipWhitelisted = $employee->isIpWhitelisted($ipPair['ipv4'], $ipPair['ipv6']);
        $ipWhitelistApplied = false;

        if ($employee->hasIpWhitelist() && !$ipWhitelisted) {
            $geoService->logAttempt(
                $employeeUser->id,
                null,
                'check_in_failed',
                'other',
                null,
                null,
                null,
                $request,
                ['error' => 'ip_not_whitelisted', 'ipv4' => $ipPair['ipv4'], 'ipv6' => $ipPair['ipv6']]
            );

            return response()->json([
                'success' => false,
                'message' => 'Your current network is not whitelisted for attendance. Please connect using an approved IP address.',
            ], 403);
        }

        // Validate request data - coordinates are optional for remote employees
        $validated = $request->validate([
            'latitude' => $geolocationRequired ? 'required|numeric|between:-90,90' : 'nullable|numeric|between:-90,90',
            'longitude' => $geolocationRequired ? 'required|numeric|between:-180,180' : 'nullable|numeric|between:-180,180',
        ]);

        // Initialize coordinates as null
        $latitude = null;
        $longitude = null;

        // Only process coordinates if geolocation is required
        if ($geolocationRequired && isset($validated['latitude']) && isset($validated['longitude'])) {
            // Normalize coordinates to 10 decimal places for consistency
            $normalized = $geoService->normalizeCoordinates($validated['latitude'], $validated['longitude']);
            $latitude = $normalized['latitude'];
            $longitude = $normalized['longitude'];
        }

        // Log for debugging
        \Log::info('Check-in attempt', [
            'employee_id' => $employeeUser->id,
            'employee_coords' => ['lat' => $latitude, 'lon' => $longitude],
            'office_coords' => ['lat' => $user->office_latitude, 'lon' => $user->office_longitude],
            'geolocation_required' => $geolocationRequired,
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

        // Prevent new check-in if there is a pending check-out from a previous day
        $pendingAttendance = Attendance::forEmployee($employeeUser->id)
            ->whereNull('check_out')
            ->orderByDesc('attendance_date')
            ->first();

        if ($pendingAttendance && $pendingAttendance->attendance_date->isBefore($today)) {
            $geoService->logAttempt(
                $employeeUser->id,
                $pendingAttendance->id,
                'check_in_failed',
                'pending_check_out',
                $latitude,
                $longitude,
                null,
                $request,
                ['pending_attendance_date' => $pendingAttendance->attendance_date->toDateString()]
            );

            return response()->json([
                'success' => false,
                'message' => 'You still have an open attendance from ' . $pendingAttendance->attendance_date->format('M d, Y') . '. Please check out first or request an attendance fix.',
            ], 400);
        }

        $distance = null;
        $radiusMeters = $user->office_radius_meters ?? 15;

        if ($geolocationRequired) {
            $hasOfficeLocation = $user->office_latitude && $user->office_longitude;

            if (!$hasOfficeLocation && $officeEnforcementEnabled) {
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

            if ($hasOfficeLocation) {
                // Calculate distance from office
                $distance = $geoService->calculateDistance(
                    $latitude,
                    $longitude,
                    $user->office_latitude,
                    $user->office_longitude
                );

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
            }

            if ($enforceOfficeRadius && $officeEnforcementEnabled && $distance !== null && $distance > $radiusMeters) {
                if ($ipWhitelisted) {
                    $ipWhitelistApplied = true;
                    \Log::info('Check-in allowed via IP whitelist override', [
                        'employee_id' => $employeeUser->id,
                        'employee_name' => $employee->name,
                        'ipv4' => $ipPair['ipv4'],
                        'ipv6' => $ipPair['ipv6'],
                        'distance_meters' => round($distance, 2),
                        'allowed_radius' => $radiusMeters,
                    ]);
                } else {
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
            } elseif ($usesWhitelistMode && $ipWhitelisted) {
                $ipWhitelistApplied = true;
                \Log::info('Check-in via geolocation whitelist mode', [
                    'employee_id' => $employeeUser->id,
                    'distance_meters' => $distance ? round($distance, 2) : null,
                    'allowed_radius' => $radiusMeters,
                    'ipv4' => $ipPair['ipv4'],
                    'ipv6' => $ipPair['ipv6'],
                ]);
            }
        } else {
            // Remote employee - log that geolocation was skipped
            \Log::info('Check-in for remote employee (geolocation not required)', [
                'employee_id' => $employeeUser->id,
                'employee_name' => $employee->name,
            ]);
        }

        // Create new attendance record with check-in time and location
        $attendance = Attendance::create([
            'employee_user_id' => $employeeUser->id,
            'attendance_date' => $today,
            'check_in' => Carbon::now(),
            'check_in_latitude' => $latitude,
            'check_in_longitude' => $longitude,
            'check_in_ip' => $ipPair['ipv4'] ?? $ipPair['ipv6'],
            'check_in_ip_v6' => $ipPair['ipv6'],
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
            $request,
            $ipWhitelistApplied ? ['ip_whitelist_override' => true] : null
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
        $employee = $employeeUser->employee()->with(['user', 'ipWhitelists'])->firstOrFail();
        $user = $employee->user;

        // Check if geolocation is required for this employee
        $geolocationRequired = $employee->requiresGeolocation();
        $enforceOfficeRadius = $employee->enforcesOfficeRadius();
        $officeEnforcementEnabled = $employee->shouldEnforceOfficeLocation();
        $usesWhitelistMode = $employee->usesWhitelistOverride();

        $ipPair = $geoService->getClientIpPair($request);
        $ipWhitelisted = $employee->isIpWhitelisted($ipPair['ipv4'], $ipPair['ipv6']);
        $ipWhitelistApplied = false;

        // Find today's attendance early for whitelist validation and subsequent checks
        $attendance = Attendance::forEmployee($employeeUser->id)
            ->where('attendance_date', $today)
            ->first();

        if (!$attendance) {
            $attendance = Attendance::forEmployee($employeeUser->id)
                ->whereNull('check_out')
                ->orderByDesc('attendance_date')
                ->orderByDesc('check_in')
                ->first();

            if ($attendance && $attendance->attendance_date->diffInDays($today) > 1) {
                $attendance = null;
            }
        }

        if ($employee->hasIpWhitelist() && !$ipWhitelisted) {
            $geoService->logAttempt(
                $employeeUser->id,
                $attendance->id ?? null,
                'check_out_failed',
                'other',
                null,
                null,
                null,
                $request,
                ['error' => 'ip_not_whitelisted', 'ipv4' => $ipPair['ipv4'], 'ipv6' => $ipPair['ipv6']]
            );

            return response()->json([
                'success' => false,
                'message' => 'Your current network is not whitelisted for attendance. Please connect using an approved IP address.',
            ], 403);
        }

        // Validate request data - coordinates are optional for remote employees
        $validated = $request->validate([
            'latitude' => $geolocationRequired ? 'required|numeric|between:-90,90' : 'nullable|numeric|between:-90,90',
            'longitude' => $geolocationRequired ? 'required|numeric|between:-180,180' : 'nullable|numeric|between:-180,180',
        ]);

        // Initialize coordinates as null
        $latitude = null;
        $longitude = null;

        // Only process coordinates if geolocation is required
        if ($geolocationRequired && isset($validated['latitude']) && isset($validated['longitude'])) {
            // Normalize coordinates to 10 decimal places for consistency
            $normalized = $geoService->normalizeCoordinates($validated['latitude'], $validated['longitude']);
            $latitude = $normalized['latitude'];
            $longitude = $normalized['longitude'];
        }

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

        // Initialize distance variable
        $distance = null;
        $radiusMeters = $user->office_radius_meters ?? 15;

        // Only validate location if geolocation is required for this employee
        if ($geolocationRequired) {
            $hasOfficeLocation = $user->office_latitude && $user->office_longitude;

            if (!$hasOfficeLocation && $officeEnforcementEnabled) {
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

            if ($hasOfficeLocation) {
                // Calculate distance from office
                $distance = $geoService->calculateDistance(
                    $latitude,
                    $longitude,
                    $user->office_latitude,
                    $user->office_longitude
                );
            }

            if ($enforceOfficeRadius && $officeEnforcementEnabled && $distance !== null && $distance > $radiusMeters) {
                if ($ipWhitelisted) {
                    $ipWhitelistApplied = true;
                    \Log::info('Check-out allowed via IP whitelist override', [
                        'employee_id' => $employeeUser->id,
                        'employee_name' => $employee->name,
                        'ipv4' => $ipPair['ipv4'],
                        'ipv6' => $ipPair['ipv6'],
                        'distance_meters' => round($distance, 2),
                        'allowed_radius' => $radiusMeters,
                    ]);
                } else {
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
            } elseif ($usesWhitelistMode && $ipWhitelisted) {
                $ipWhitelistApplied = true;
                \Log::info('Check-out via geolocation whitelist mode', [
                    'employee_id' => $employeeUser->id,
                    'distance_meters' => $distance ? round($distance, 2) : null,
                    'allowed_radius' => $radiusMeters,
                    'ipv4' => $ipPair['ipv4'],
                    'ipv6' => $ipPair['ipv6'],
                ]);
            }
        } else {
            // Remote employee - log that geolocation was skipped
            \Log::info('Check-out for remote employee (geolocation not required)', [
                'employee_id' => $employeeUser->id,
                'employee_name' => $employee->name,
            ]);
        }

        // Update attendance with check-out time and location
        $attendance->update([
            'check_out' => Carbon::now(),
            'check_out_latitude' => $latitude,
            'check_out_longitude' => $longitude,
            'check_out_ip' => $ipPair['ipv4'] ?? $ipPair['ipv6'],
            'check_out_ip_v6' => $ipPair['ipv6'],
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
            $request,
            $ipWhitelistApplied ? ['ip_whitelist_override' => true] : null
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
