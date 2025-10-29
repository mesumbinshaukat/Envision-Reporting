<?php

namespace App\Policies;

use App\Models\SalaryRelease;
use App\Models\User;

class SalaryReleasePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SalaryRelease $bonus): bool
    {
        return $user->id === $bonus->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, SalaryRelease $bonus): bool
    {
        return $user->id === $bonus->user_id;
    }

    public function delete(User $user, SalaryRelease $bonus): bool
    {
        return $user->id === $bonus->user_id;
    }

    public function restore(User $user, SalaryRelease $bonus): bool
    {
        return $user->id === $bonus->user_id;
    }

    public function forceDelete(User $user, SalaryRelease $bonus): bool
    {
        return $user->id === $bonus->user_id;
    }
}