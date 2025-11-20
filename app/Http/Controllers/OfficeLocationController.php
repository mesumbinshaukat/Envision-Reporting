<?php

namespace App\Http\Controllers;

use App\Services\GeolocationService;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LocationCalibration;
use Illuminate\Support\Facades\Auth;

class OfficeLocationController extends Controller
{
    /**
     * Display the office location settings page.
     */
    public function index()
    {
        $user = Auth::user();
        
        return view('admin.office-location.index', compact('user'));
    }

    /**
     * Update office location settings.
     */
    public function update(Request $request, GeolocationService $geoService)
    {
        $validated = $request->validate([
            'office_latitude' => 'required|numeric|between:-90,90',
            'office_longitude' => 'required|numeric|between:-180,180',
            'office_radius_meters' => 'required|integer|min:5|max:1000',
            'gps_latitude' => 'nullable|numeric|between:-90,90',
            'gps_longitude' => 'nullable|numeric|between:-180,180',
            'gps_accuracy' => 'nullable|numeric|min:0',
        ]);

        $user = Auth::user();
        
        // Validate coordinates
        if (!$geoService->validateCoordinates($validated['office_latitude'], $validated['office_longitude'])) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Invalid coordinates provided.');
        }

        $user->update([
            'office_latitude' => $validated['office_latitude'],
            'office_longitude' => $validated['office_longitude'],
            'office_radius_meters' => $validated['office_radius_meters'],
        ]);

        // Create calibration point if GPS data provided
        if (isset($validated['gps_latitude']) && isset($validated['gps_longitude'])) {
            // Log the incoming data for debugging
            \Log::info('Calibration Data Received:', [
                'office_latitude' => $validated['office_latitude'],
                'office_longitude' => $validated['office_longitude'],
                'gps_latitude' => $validated['gps_latitude'],
                'gps_longitude' => $validated['gps_longitude'],
                'gps_accuracy' => $validated['gps_accuracy'] ?? null,
            ]);
            
            // Calculate offsets
            $latOffset = $validated['office_latitude'] - $validated['gps_latitude'];
            $lonOffset = $validated['office_longitude'] - $validated['gps_longitude'];
            
            \Log::info('Calculated Offsets:', [
                'latitude_offset' => $latOffset,
                'longitude_offset' => $lonOffset,
            ]);
            
            // Deactivate old calibrations
            LocationCalibration::where('user_id', $user->id)->update(['is_active' => false]);
            
            // Create new calibration
            $calibration = LocationCalibration::create([
                'user_id' => $user->id,
                'label' => 'Office Location - Admin Set',
                'known_latitude' => $validated['office_latitude'],
                'known_longitude' => $validated['office_longitude'],
                'gps_latitude' => $validated['gps_latitude'],
                'gps_longitude' => $validated['gps_longitude'],
                'gps_accuracy' => $validated['gps_accuracy'] ?? null,
                'latitude_offset' => $latOffset,
                'longitude_offset' => $lonOffset,
                'is_active' => true,
                'device_info' => $request->header('User-Agent'),
            ]);
            
            \Log::info('Calibration Created:', $calibration->toArray());
        } else {
            \Log::warning('No GPS data provided for calibration');
        }

        return redirect()->route('admin.office-location.index')
            ->with('success', 'Office location and calibration data updated successfully.');
    }

    /**
     * Toggle enforcement of office location radius checks.
     */
    public function toggleEnforcement(Request $request)
    {
        $admin = Auth::guard('web')->user();

        if (!$admin) {
            abort(403, 'Only administrators can toggle office location enforcement.');
        }

        $validated = $request->validate([
            'enforce_office_location' => 'required|boolean',
        ]);

        $desiredState = (bool) $validated['enforce_office_location'];

        if ($admin->enforce_office_location !== $desiredState) {
            $admin->update([
                'enforce_office_location' => $desiredState,
            ]);

            \Log::info('Office location enforcement updated.', [
                'admin_id' => $admin->id,
                'enforce_office_location' => $desiredState,
            ]);
        }

        $message = $desiredState
            ? 'Office location enforcement enabled. Employees must be within the configured radius to check in or out.'
            : 'Office location enforcement disabled. Radius checks are temporarily relaxed for all employees.';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'enforce_office_location' => $desiredState,
                'message' => $message,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Get current location from browser.
     */
    public function getCurrentLocation(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        return response()->json([
            'success' => true,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
        ]);
    }

    /**
     * Get active calibration data for current user.
     */
    public function getCalibration()
    {
        // Determine the admin user ID based on auth guard
        if (Auth::guard('web')->check()) {
            // Admin user
            $userId = Auth::guard('web')->id();
        } elseif (Auth::guard('employee')->check()) {
            // Employee user - get their admin's user ID
            $employeeUser = Auth::guard('employee')->user();
            $userId = $employeeUser->employee->user_id;
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $calibration = LocationCalibration::getActiveCalibration($userId);

        if (!$calibration) {
            return response()->json([
                'success' => false,
                'message' => 'No calibration data available',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'calibration' => [
                'latitude_offset' => (float) $calibration->latitude_offset,
                'longitude_offset' => (float) $calibration->longitude_offset,
                'known_latitude' => (float) $calibration->known_latitude,
                'known_longitude' => (float) $calibration->known_longitude,
                'gps_accuracy' => (float) $calibration->gps_accuracy,
                'label' => $calibration->label,
                'created_at' => $calibration->created_at->toDateTimeString(),
            ],
        ]);
    }
}
