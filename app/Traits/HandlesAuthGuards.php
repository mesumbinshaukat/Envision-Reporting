<?php

namespace App\Traits;

trait HandlesAuthGuards
{
    /**
     * Get the current user ID regardless of guard
     */
    protected function getCurrentUserId()
    {
        if (auth()->guard('employee')->check()) {
            return auth()->guard('employee')->user()->admin_id;
        }

        $user = auth()->guard('web')->user();
        if ($user instanceof \App\Models\User) {
            return $user->tenantId();
        }

        return auth()->id();
    }

    /**
     * Get the current user regardless of guard
     */
    protected function getCurrentUser()
    {
        if (auth()->guard('employee')->check()) {
            return auth()->guard('employee')->user();
        }
        
        return auth()->user();
    }

    /**
     * Check if current user is an employee
     */
    protected function isEmployee()
    {
        return auth()->guard('employee')->check();
    }

    /**
     * Check if current user is an admin
     */
    protected function isAdmin()
    {
        return auth()->guard('web')->check();
    }
}
