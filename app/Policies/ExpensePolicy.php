<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Expense $invoice): bool
    {
        return $user->id === $invoice->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Expense $invoice): bool
    {
        return $user->id === $invoice->user_id;
    }

    public function delete(User $user, Expense $invoice): bool
    {
        return $user->id === $invoice->user_id;
    }

    public function restore(User $user, Expense $invoice): bool
    {
        return $user->id === $invoice->user_id;
    }

    public function forceDelete(User $user, Expense $invoice): bool
    {
        return $user->id === $invoice->user_id;
    }
}