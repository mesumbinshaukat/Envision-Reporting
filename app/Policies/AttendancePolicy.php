<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;
use App\Models\EmployeeUser;

class AttendancePolicy
{
    /**
     * Determine if the given attendance can be viewed by the user.
     */
    public function view($user, Attendance $attendance): bool
    {
        // Admins can view all attendance records
        if ($user instanceof User && $user->isAdmin()) {
            return true;
        }

        // Employees can only view their own attendance
        if ($user instanceof EmployeeUser && $user->isEmployee()) {
            return $attendance->employee_user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if the user can view any attendance records.
     */
    public function viewAny($user): bool
    {
        // Both admins and employees can view attendance (with different scopes)
        return ($user instanceof User && $user->isAdmin()) || 
               ($user instanceof EmployeeUser && $user->isEmployee());
    }

    /**
     * Determine if the user can create attendance records.
     */
    public function create($user): bool
    {
        // Only admins can manually create attendance records
        return $user instanceof User && $user->isAdmin();
    }

    /**
     * Determine if the user can update the given attendance.
     */
    public function update($user, Attendance $attendance): bool
    {
        // Only admins can update attendance records
        return $user instanceof User && $user->isAdmin();
    }

    /**
     * Determine if the user can delete the given attendance.
     */
    public function delete($user, Attendance $attendance): bool
    {
        // Only admins can delete attendance records
        return $user instanceof User && $user->isAdmin();
    }

    /**
     * Determine if the employee can check in.
     */
    public function checkIn(EmployeeUser $employeeUser): bool
    {
        // Employees can check in
        return $employeeUser->isEmployee();
    }

    /**
     * Determine if the employee can check out.
     */
    public function checkOut(EmployeeUser $employeeUser, Attendance $attendance): bool
    {
        // Employees can check out only their own attendance
        return $employeeUser->isEmployee() && 
               $attendance->employee_user_id === $employeeUser->id;
    }

    /**
     * Determine if the employee can request a fix.
     */
    public function requestFix(EmployeeUser $employeeUser, Attendance $attendance): bool
    {
        // Employees can request fixes only for their own attendance
        return $employeeUser->isEmployee() && 
               $attendance->employee_user_id === $employeeUser->id;
    }
}
