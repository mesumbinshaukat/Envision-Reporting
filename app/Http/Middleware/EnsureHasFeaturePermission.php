<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use App\Models\EmployeeUser;

class EnsureHasFeaturePermission
{
    public function handle(Request $request, Closure $next, string $featureKey, string $ability = 'read'): Response
    {
        // API requests (Sanctum) - use the authenticated token user.
        if ($request->expectsJson()) {
            $apiUser = $request->user();

            // Keep existing API behavior for employee tokens: controllers already return
            // the project-specific forbidden payload/messages. Middleware should not
            // change those responses.
            if ($apiUser instanceof EmployeeUser) {
                return $next($request);
            }

            if (!$apiUser instanceof User) {
                return response()->json([
                    'success' => false,
                    'message' => 'This action is unauthorized.',
                ], 403);
            }

            $apiUser->loadMissing('featurePermissions');

            $allowed = match ($ability) {
                'read' => $apiUser->canReadFeature($featureKey),
                'write' => $apiUser->canWriteFeature($featureKey),
                'both' => $apiUser->canReadFeature($featureKey) && $apiUser->canWriteFeature($featureKey),
                default => false,
            };

            if (!$allowed) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to access this feature.',
                ], 403);
            }

            return $next($request);
        }

        // Employee guard remains governed by existing employee rules/policies.
        if (auth()->guard('employee')->check()) {
            return $next($request);
        }

        if (!auth()->guard('web')->check()) {
            return redirect()->route('login');
        }

        $user = auth()->guard('web')->user();

        if (!$user instanceof User) {
            abort(403, 'This action is unauthorized.');
        }

        // Ensure permissions are available without extra query spam.
        $user->loadMissing('featurePermissions');

        $allowed = match ($ability) {
            'read' => $user->canReadFeature($featureKey),
            'write' => $user->canWriteFeature($featureKey),
            'both' => $user->canReadFeature($featureKey) && $user->canWriteFeature($featureKey),
            default => false,
        };

        if (!$allowed) {
            abort(403, 'You do not have permission to access this feature.');
        }

        return $next($request);
    }
}

