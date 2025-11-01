<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use App\Models\EmployeeUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the given invoice can be viewed by the user.
     */
    public function viewAny($user): bool
    {
        return true;
    }

    /**
     * Determine if the given invoice can be viewed by the user.
     */
    public function view($user, Invoice $invoice): bool
    {
        // Admin user
        if ($user instanceof User) {
            return $user->id === $invoice->user_id;
        }
        
        // Employee user
        if ($user instanceof EmployeeUser) {
            return $user->admin_id === $invoice->user_id;
        }
        
        return false;
    }

    /**
     * Determine if the user can create invoices.
     */
    public function create($user): bool
    {
        return true;
    }

    /**
     * Determine if the given invoice can be updated by the user.
     */
    public function update($user, Invoice $invoice): bool
    {
        // Admin user
        if ($user instanceof User) {
            return $user->id === $invoice->user_id;
        }
        
        // Employee user - can update if they created it and it's not approved yet
        if ($user instanceof EmployeeUser) {
            return $user->admin_id === $invoice->user_id 
                && ($user->id === $invoice->created_by_employee_id || $invoice->approval_status === 'approved');
        }
        
        return false;
    }

    /**
     * Determine if the given invoice can be deleted by the user.
     */
    public function delete($user, Invoice $invoice): bool
    {
        // Only admin can delete
        if ($user instanceof User) {
            return $user->id === $invoice->user_id;
        }
        
        return false;
    }

    /**
     * Determine if the given invoice can be restored by the user.
     */
    public function restore($user, Invoice $invoice): bool
    {
        // Only admin can restore
        if ($user instanceof User) {
            return $user->id === $invoice->user_id;
        }
        
        return false;
    }

    /**
     * Determine if the given invoice can be permanently deleted by the user.
     */
    public function forceDelete($user, Invoice $invoice): bool
    {
        // Only admin can force delete
        if ($user instanceof User) {
            return $user->id === $invoice->user_id;
        }
        
        return false;
    }
}