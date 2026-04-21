<?php

namespace App\Policies;

use App\Models\EmployeeAllowance;
use App\Models\User;

class EmployeeAllowancePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, EmployeeAllowance $employeeAllowance): bool
    {
        return $user->tenantId() === $employeeAllowance->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, EmployeeAllowance $employeeAllowance): bool
    {
        return $user->tenantId() === $employeeAllowance->user_id;
    }

    public function delete(User $user, EmployeeAllowance $employeeAllowance): bool
    {
        return $user->tenantId() === $employeeAllowance->user_id;
    }

    public function restore(User $user, EmployeeAllowance $employeeAllowance): bool
    {
        return $user->tenantId() === $employeeAllowance->user_id;
    }

    public function forceDelete(User $user, EmployeeAllowance $employeeAllowance): bool
    {
        return $user->tenantId() === $employeeAllowance->user_id;
    }
}
