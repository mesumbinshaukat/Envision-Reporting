<?php

namespace App\Policies;

use App\Models\EmployeeActivityLog;
use App\Models\User;

class EmployeeActivityLogPolicy
{
    /**
     * Determine whether the user can view any logs.
     */
    public function viewAny(?User $user): bool
    {
        return $user !== null;
    }

    /**
     * Determine whether the user can view the log.
     */
    public function view(?User $user, EmployeeActivityLog $log): bool
    {
        if (!$user) {
            return false;
        }

        return $log->admin_id === $user->id;
    }
}
