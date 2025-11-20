<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Models\EmployeeUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseApiController
{
    /**
     * Admin user login
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Create token with admin abilities
        $token = $user->createToken('admin-token', ['admin'])->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'type' => 'admin',
                'profile_photo_url' => $user->profile_photo_url,
            ],
        ], 'Login successful');
    }

    /**
     * Employee user login
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function employeeLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $employeeUser = EmployeeUser::where('email', $request->email)->first();

        if (!$employeeUser || !Hash::check($request->password, $employeeUser->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Load employee relationship
        $employeeUser->load('employee');

        // Create token with employee abilities
        $token = $employeeUser->createToken('employee-token', ['employee'])->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $employeeUser->id,
                'name' => $employeeUser->name,
                'email' => $employeeUser->email,
                'type' => 'employee',
                'employee_id' => $employeeUser->employee_id,
                'admin_id' => $employeeUser->admin_id,
                'profile_photo_url' => $employeeUser->profile_photo_url,
            ],
        ], 'Login successful');
    }

    /**
     * Logout (revoke current token)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Revoke the current token
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully');
    }

    /**
     * Logout from all devices (revoke all tokens)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logoutAll(Request $request)
    {
        // Revoke all tokens
        $request->user()->tokens()->delete();

        return $this->success(null, 'Logged out from all devices successfully');
    }

    /**
     * Get authenticated user details
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $user = $request->user();
        
        // Determine user type based on model
        if ($user instanceof EmployeeUser) {
            $user->load('employee');
            
            return $this->success([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'type' => 'employee',
                'employee_id' => $user->employee_id,
                'admin_id' => $user->admin_id,
                'profile_photo_url' => $user->profile_photo_url,
                'employee' => $user->employee ? [
                    'id' => $user->employee->id,
                    'name' => $user->employee->name,
                    'role' => $user->employee->role,
                    'employment_type' => $user->employee->employment_type,
                ] : null,
            ]);
        }
        
        // Admin user
        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'type' => 'admin',
            'profile_photo_url' => $user->profile_photo_url,
            'office_latitude' => $user->office_latitude,
            'office_longitude' => $user->office_longitude,
            'office_radius_meters' => $user->office_radius_meters,
            'enforce_office_location' => $user->enforce_office_location,
        ]);
    }

    /**
     * Refresh token (extend expiration)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        $user = $request->user();
        $currentToken = $request->user()->currentAccessToken();
        
        // Get token abilities
        $abilities = $currentToken->abilities;
        
        // Delete current token
        $currentToken->delete();
        
        // Create new token with same abilities
        $tokenName = in_array('admin', $abilities) ? 'admin-token' : 'employee-token';
        $newToken = $user->createToken($tokenName, $abilities)->plainTextToken;

        return $this->success([
            'token' => $newToken,
            'token_type' => 'Bearer',
        ], 'Token refreshed successfully');
    }
}
