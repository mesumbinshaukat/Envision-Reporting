<?php

namespace App\Policies;

use App\Models\AllowanceType;
use App\Models\User;

class AllowanceTypePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AllowanceType $allowanceType): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AllowanceType $allowanceType): bool
    {
        return true;
    }

    public function delete(User $user, AllowanceType $allowanceType): bool
    {
        return true;
    }

    public function restore(User $user, AllowanceType $allowanceType): bool
    {
        return true;
    }

    public function forceDelete(User $user, AllowanceType $allowanceType): bool
    {
        return true;
    }
}
