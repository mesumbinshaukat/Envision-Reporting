<?php

namespace App\Policies;

use App\Models\Bonus;
use App\Models\User;

class BonusPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Bonus $expense): bool
    {
        return $user->id === $expense->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Bonus $expense): bool
    {
        return $user->id === $expense->user_id;
    }

    public function delete(User $user, Bonus $expense): bool
    {
        return $user->id === $expense->user_id;
    }

    public function restore(User $user, Bonus $expense): bool
    {
        return $user->id === $expense->user_id;
    }

    public function forceDelete(User $user, Bonus $expense): bool
    {
        return $user->id === $expense->user_id;
    }
}