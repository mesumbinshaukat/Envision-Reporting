<?php

namespace App\Policies;

use App\Models\EmployeeIpWhitelist;
use App\Models\User;

class EmployeeIpWhitelistPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, EmployeeIpWhitelist $ipWhitelist): bool
    {
        if (!$user->isAdmin()) {
            return false;
        }

        return optional($ipWhitelist->employee?->user)->is($user);
    }
}
