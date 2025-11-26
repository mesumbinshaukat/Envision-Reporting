<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Models\EmployeeUser;
use Illuminate\Http\Request;

class SettingsApiController extends BaseApiController
{
    /**
     * Get IP whitelist enforcement status
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIpWhitelistStatus(Request $request)
    {
        $user = $request->user();
        
        // Get admin user based on token type
        if ($user->tokenCan('admin')) {
            $admin = $user;
        } elseif ($user->tokenCan('employee')) {
            // Employee user - get their admin
            $employeeUser = EmployeeUser::with('employee.user')->find($user->id);
            
            if (!$employeeUser || !$employeeUser->employee || !$employeeUser->employee->user) {
                return $this->error('Unable to retrieve admin settings', 404);
            }
            
            $admin = $employeeUser->employee->user;
        } else {
            return $this->forbidden('Invalid token permissions');
        }

        return $this->success([
            'enforce_ip_whitelist' => (bool) $admin->enforce_ip_whitelist,
            'enabled' => (bool) $admin->enforce_ip_whitelist,
        ], 'IP whitelist enforcement status retrieved successfully');
    }

    /**
     * Get location guard (office location) enforcement status
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLocationGuardStatus(Request $request)
    {
        $user = $request->user();
        
        // Get admin user based on token type
        if ($user->tokenCan('admin')) {
            $admin = $user;
        } elseif ($user->tokenCan('employee')) {
            // Employee user - get their admin
            $employeeUser = EmployeeUser::with('employee.user')->find($user->id);
            
            if (!$employeeUser || !$employeeUser->employee || !$employeeUser->employee->user) {
                return $this->error('Unable to retrieve admin settings', 404);
            }
            
            $admin = $employeeUser->employee->user;
        } else {
            return $this->forbidden('Invalid token permissions');
        }

        $hasOfficeLocation = !is_null($admin->office_latitude) && !is_null($admin->office_longitude);

        return $this->success([
            'enforce_office_location' => (bool) $admin->enforce_office_location,
            'enabled' => (bool) $admin->enforce_office_location,
            'office_configured' => $hasOfficeLocation,
            'office_latitude' => $admin->office_latitude,
            'office_longitude' => $admin->office_longitude,
            'office_radius_meters' => $admin->office_radius_meters ?? 15,
        ], 'Location guard enforcement status retrieved successfully');
    }

    /**
     * Get combined attendance settings for mobile app
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAttendanceSettings(Request $request)
    {
        $user = $request->user();
        
        // Get admin user and employee info based on token type
        if ($user->tokenCan('admin')) {
            $admin = $user;
            $employee = null;
            $employeeUser = null;
        } elseif ($user->tokenCan('employee')) {
            // Employee user - get their admin and employee info
            $employeeUser = EmployeeUser::with('employee.user', 'employee.ipWhitelists')->find($user->id);
            
            if (!$employeeUser || !$employeeUser->employee || !$employeeUser->employee->user) {
                return $this->error('Unable to retrieve admin settings', 404);
            }
            
            $admin = $employeeUser->employee->user;
            $employee = $employeeUser->employee;
        } else {
            return $this->forbidden('Invalid token permissions');
        }

        $hasOfficeLocation = !is_null($admin->office_latitude) && !is_null($admin->office_longitude);

        $response = [
            'ip_whitelist' => [
                'enforce_ip_whitelist' => (bool) $admin->enforce_ip_whitelist,
                'enabled' => (bool) $admin->enforce_ip_whitelist,
            ],
            'location_guard' => [
                'enforce_office_location' => (bool) $admin->enforce_office_location,
                'enabled' => (bool) $admin->enforce_office_location,
                'office_configured' => $hasOfficeLocation,
                'office_latitude' => $admin->office_latitude,
                'office_longitude' => $admin->office_longitude,
                'office_radius_meters' => $admin->office_radius_meters ?? 15,
            ],
        ];

        // Add employee-specific settings if this is an employee request
        if ($employee) {
            $response['employee'] = [
                'geolocation_mode' => $employee->geolocation_mode,
                'geolocation_required' => $employee->requiresGeolocation(),
                'enforces_office_radius' => $employee->enforcesOfficeRadius(),
                'uses_whitelist_override' => $employee->usesWhitelistOverride(),
                'has_ip_whitelist' => $employee->hasIpWhitelist(),
                'ip_whitelists_count' => $employee->ipWhitelists->count(),
            ];
        }

        return $this->success($response, 'Attendance settings retrieved successfully');
    }
}
