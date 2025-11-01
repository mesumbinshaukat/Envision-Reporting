<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use App\Models\EmployeeUser;
use Illuminate\Auth\Access\Response;

class ClientPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny($user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view($user, Client $client): bool
    {
        // Admin user
        if ($user instanceof User) {
            return $user->id === $client->user_id;
        }
        
        // Employee user
        if ($user instanceof EmployeeUser) {
            return $user->admin_id === $client->user_id;
        }
        
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create($user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update($user, Client $client): bool
    {
        // Admin user
        if ($user instanceof User) {
            return $user->id === $client->user_id;
        }
        
        // Employee user
        if ($user instanceof EmployeeUser) {
            return $user->admin_id === $client->user_id;
        }
        
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete($user, Client $client): bool
    {
        // Admin user
        if ($user instanceof User) {
            return $user->id === $client->user_id;
        }
        
        // Employee user
        if ($user instanceof EmployeeUser) {
            return $user->admin_id === $client->user_id;
        }
        
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore($user, Client $client): bool
    {
        // Only admin can restore
        if ($user instanceof User) {
            return $user->id === $client->user_id;
        }
        
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete($user, Client $client): bool
    {
        // Only admin can force delete
        if ($user instanceof User) {
            return $user->id === $client->user_id;
        }
        
        return false;
    }
}
