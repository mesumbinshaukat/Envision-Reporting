<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Invoice;
use App\Models\Expense;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\EmployeeUser;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->tokenCan('admin')) {
            return $this->adminDashboard($user);
        } else {
            return $this->employeeDashboard($user);
        }
    }

    private function adminDashboard($user)
    {
        $userId = $user->id;
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Invoice statistics
        $totalInvoices = Invoice::where('user_id', $userId)->count();
        $pendingInvoices = Invoice::where('user_id', $userId)->where('status', 'pending')->count();
        $paidInvoices = Invoice::where('user_id', $userId)->where('status', 'paid')->count();
        $totalRevenue = Invoice::where('user_id', $userId)->sum('paid_amount');

        // Monthly revenue
        $monthlyRevenue = Invoice::where('user_id', $userId)
            ->whereMonth('payment_date', $currentMonth)
            ->whereYear('payment_date', $currentYear)
            ->sum('paid_amount');

        // Employee statistics
        $totalEmployees = Employee::where('user_id', $userId)->count();
        $activeEmployees = Employee::where('user_id', $userId)->whereNull('last_date')->count();

        // Expense statistics
        $monthlyExpenses = Expense::where('user_id', $userId)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->sum('amount');

        // Attendance statistics
        $employeeUserIds = EmployeeUser::where('admin_id', $userId)->pluck('id');
        $todayAttendance = Attendance::whereIn('employee_user_id', $employeeUserIds)
            ->whereDate('attendance_date', Carbon::today())
            ->count();
        $checkedInToday = Attendance::whereIn('employee_user_id', $employeeUserIds)
            ->whereDate('attendance_date', Carbon::today())
            ->whereNotNull('check_in')
            ->count();

        return $this->success([
            'invoices' => [
                'total' => $totalInvoices,
                'pending' => $pendingInvoices,
                'paid' => $paidInvoices,
                'total_revenue' => $totalRevenue,
                'monthly_revenue' => $monthlyRevenue,
            ],
            'employees' => [
                'total' => $totalEmployees,
                'active' => $activeEmployees,
            ],
            'expenses' => [
                'monthly_total' => $monthlyExpenses,
            ],
            'attendance' => [
                'today_records' => $todayAttendance,
                'checked_in_today' => $checkedInToday,
            ],
        ]);
    }

    private function employeeDashboard($user)
    {
        $employeeUserId = $user->id;
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Attendance statistics
        $monthlyAttendance = Attendance::where('employee_user_id', $employeeUserId)
            ->whereMonth('attendance_date', $currentMonth)
            ->whereYear('attendance_date', $currentYear)
            ->count();

        $todayAttendance = Attendance::where('employee_user_id', $employeeUserId)
            ->whereDate('attendance_date', Carbon::today())
            ->first();

        // Invoice statistics (invoices created by or assigned to employee)
        $myInvoices = Invoice::where(function($q) use ($user) {
            $q->where('created_by_employee_id', $user->id)
              ->orWhere('employee_id', $user->employee_id);
        })->count();

        $pendingApproval = Invoice::where('created_by_employee_id', $user->id)
            ->where('approval_status', 'pending')
            ->count();

        return $this->success([
            'attendance' => [
                'monthly_records' => $monthlyAttendance,
                'today_checked_in' => $todayAttendance ? $todayAttendance->hasCheckedIn() : false,
                'today_checked_out' => $todayAttendance ? $todayAttendance->hasCheckedOut() : false,
            ],
            'invoices' => [
                'total' => $myInvoices,
                'pending_approval' => $pendingApproval,
            ],
        ]);
    }
}
