<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EmployeeApiController;
use App\Http\Controllers\Api\V1\AttendanceApiController;
use App\Http\Controllers\Api\V1\InvoiceApiController;
use App\Http\Controllers\Api\V1\ClientApiController;
use App\Http\Controllers\Api\V1\ExpenseApiController;
use App\Http\Controllers\Api\V1\BonusApiController;
use App\Http\Controllers\Api\V1\SalaryReleaseApiController;
use App\Http\Controllers\Api\V1\CurrencyApiController;
use App\Http\Controllers\Api\V1\DashboardApiController;
use App\Http\Controllers\Api\V1\SettingsApiController;
use App\Http\Controllers\Api\V1\OfficeScheduleApiController;
use App\Http\Controllers\Api\V1\OfficeClosureApiController;
use App\Http\Controllers\Api\V1\AttendanceLogApiController;
use App\Http\Controllers\Api\V1\ActivityLogApiController;
use App\Http\Controllers\Api\V1\FixRequestApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API Version 1 - prefix is set in bootstrap/app.php
Route::middleware([])->group(function () {
    
    // Authentication routes (public)
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/employee/login', [AuthController::class, 'employeeLogin']);
    
    // Protected routes (require authentication)
    Route::middleware(['auth:sanctum'])->group(function () {
        
        // Authentication management
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        
        // Dashboard
        Route::get('/dashboard', [DashboardApiController::class, 'index']);
        
        // Settings
        Route::get('/settings/ip-whitelist-status', [SettingsApiController::class, 'getIpWhitelistStatus']);
        Route::get('/settings/location-guard-status', [SettingsApiController::class, 'getLocationGuardStatus']);
        Route::get('/settings/attendance', [SettingsApiController::class, 'getAttendanceSettings']);
        
        // Employees (admin only) - manual routes to avoid name conflicts
        Route::get('/employees', [EmployeeApiController::class, 'index'])->middleware('feature:employees,read');
        Route::post('/employees', [EmployeeApiController::class, 'store'])->middleware('feature:employees,write');
        Route::get('/employees/{id}', [EmployeeApiController::class, 'show'])->middleware('feature:employees,read');
        Route::put('/employees/{id}', [EmployeeApiController::class, 'update'])->middleware('feature:employees,write');
        Route::patch('/employees/{id}', [EmployeeApiController::class, 'update'])->middleware('feature:employees,write');
        Route::delete('/employees/{id}', [EmployeeApiController::class, 'destroy'])->middleware('feature:employees,write');
        Route::post('/employees/{id}/toggle-geolocation', [EmployeeApiController::class, 'toggleGeolocation'])->middleware('feature:employees,write');
        Route::post('/employees/bulk-action', [EmployeeApiController::class, 'bulkAction'])->middleware('feature:employees,write');
        
        // Attendance
        Route::get('/attendance', [AttendanceApiController::class, 'index']);
        Route::post('/attendance/check-in', [AttendanceApiController::class, 'checkIn']);
        Route::post('/attendance/check-out', [AttendanceApiController::class, 'checkOut']);
        Route::get('/attendance/status', [AttendanceApiController::class, 'getCurrentStatus']);
        Route::get('/attendance/statistics', [AttendanceApiController::class, 'statistics']);
        Route::get('/attendance/{id}', [AttendanceApiController::class, 'show']);
        
        // Office Schedule
        Route::get('/office-schedule', [OfficeScheduleApiController::class, 'getSchedule'])->middleware('feature:attendance_admin,read');
        Route::put('/office-schedule', [OfficeScheduleApiController::class, 'updateSchedule'])->middleware('feature:attendance_admin,write');
        
        // Office Closures
        Route::get('/office-closures', [OfficeClosureApiController::class, 'index'])->middleware('feature:attendance_admin,read');
        Route::post('/office-closures', [OfficeClosureApiController::class, 'store'])->middleware('feature:attendance_admin,write');
        Route::get('/office-closures/{id}', [OfficeClosureApiController::class, 'show'])->middleware('feature:attendance_admin,read');
        Route::delete('/office-closures/{id}', [OfficeClosureApiController::class, 'destroy'])->middleware('feature:attendance_admin,write');
        
        // Attendance Logs (Admin only)
        Route::get('/attendance-logs', [AttendanceLogApiController::class, 'index'])->middleware('feature:attendance_logs,read');
        Route::get('/attendance-logs/{id}', [AttendanceLogApiController::class, 'show'])->middleware('feature:attendance_logs,read');
        Route::post('/attendance-logs/cleanup', [AttendanceLogApiController::class, 'cleanup'])->middleware('feature:attendance_logs,write');
        
        // Activity Logs (Admin only)
        Route::get('/activity-logs', [ActivityLogApiController::class, 'index'])->middleware('feature:activity_logs,read');
        Route::get('/activity-logs/{id}', [ActivityLogApiController::class, 'show'])->middleware('feature:activity_logs,read');
        Route::post('/activity-logs/cleanup', [ActivityLogApiController::class, 'cleanup'])->middleware('feature:activity_logs,write');
        
        // Fix Requests
        Route::get('/fix-requests', [FixRequestApiController::class, 'index'])->middleware('feature:attendance_admin,read');
        Route::post('/fix-requests', [FixRequestApiController::class, 'store'])->middleware('feature:attendance_admin,write');
        Route::get('/fix-requests/{id}', [FixRequestApiController::class, 'show'])->middleware('feature:attendance_admin,read');
        Route::post('/fix-requests/{id}/process', [FixRequestApiController::class, 'process'])->middleware('feature:attendance_admin,write');
        
        // Clients - manual routes to avoid name conflicts
        Route::get('/clients', [ClientApiController::class, 'index']);
        Route::post('/clients', [ClientApiController::class, 'store']);
        Route::get('/clients/{id}', [ClientApiController::class, 'show']);
        Route::put('/clients/{id}', [ClientApiController::class, 'update']);
        Route::patch('/clients/{id}', [ClientApiController::class, 'update']);
        Route::delete('/clients/{id}', [ClientApiController::class, 'destroy']);
        
        // Invoices - manual routes to avoid name conflicts
        Route::get('/invoices', [InvoiceApiController::class, 'index']);
        Route::post('/invoices', [InvoiceApiController::class, 'store']);
        Route::get('/invoices/{id}', [InvoiceApiController::class, 'show']);
        Route::put('/invoices/{id}', [InvoiceApiController::class, 'update']);
        Route::patch('/invoices/{id}', [InvoiceApiController::class, 'update']);
        Route::delete('/invoices/{id}', [InvoiceApiController::class, 'destroy']);
        Route::get('/invoices/{id}/pdf', [InvoiceApiController::class, 'pdf']);
        Route::post('/invoices/{id}/approve', [InvoiceApiController::class, 'approve']);
        Route::post('/invoices/{id}/reject', [InvoiceApiController::class, 'reject']);
        
        // Expenses (admin only) - manual routes to avoid name conflicts
        Route::get('/expenses', [ExpenseApiController::class, 'index'])->middleware('feature:expenses,read');
        Route::post('/expenses', [ExpenseApiController::class, 'store'])->middleware('feature:expenses,write');
        Route::get('/expenses/{id}', [ExpenseApiController::class, 'show'])->middleware('feature:expenses,read');
        Route::put('/expenses/{id}', [ExpenseApiController::class, 'update'])->middleware('feature:expenses,write');
        Route::patch('/expenses/{id}', [ExpenseApiController::class, 'update'])->middleware('feature:expenses,write');
        Route::delete('/expenses/{id}', [ExpenseApiController::class, 'destroy'])->middleware('feature:expenses,write');
        
        // Bonuses (admin only) - manual routes to avoid name conflicts
        Route::get('/bonuses', [BonusApiController::class, 'index'])->middleware('feature:bonuses,read');
        Route::post('/bonuses', [BonusApiController::class, 'store'])->middleware('feature:bonuses,write');
        Route::get('/bonuses/{id}', [BonusApiController::class, 'show'])->middleware('feature:bonuses,read');
        Route::put('/bonuses/{id}', [BonusApiController::class, 'update'])->middleware('feature:bonuses,write');
        Route::patch('/bonuses/{id}', [BonusApiController::class, 'update'])->middleware('feature:bonuses,write');
        Route::delete('/bonuses/{id}', [BonusApiController::class, 'destroy'])->middleware('feature:bonuses,write');
        
        // Salary Releases (admin only) - manual routes to avoid name conflicts
        Route::get('/salary-releases', [SalaryReleaseApiController::class, 'index'])->middleware('feature:salary_releases,read');
        Route::post('/salary-releases', [SalaryReleaseApiController::class, 'store'])->middleware('feature:salary_releases,write');
        Route::get('/salary-releases/{id}', [SalaryReleaseApiController::class, 'show'])->middleware('feature:salary_releases,read');
        Route::put('/salary-releases/{id}', [SalaryReleaseApiController::class, 'update'])->middleware('feature:salary_releases,write');
        Route::patch('/salary-releases/{id}', [SalaryReleaseApiController::class, 'update'])->middleware('feature:salary_releases,write');
        Route::delete('/salary-releases/{id}', [SalaryReleaseApiController::class, 'destroy'])->middleware('feature:salary_releases,write');
        Route::post('/salary-releases/preview', [SalaryReleaseApiController::class, 'preview'])->middleware('feature:salary_releases,write');
        Route::get('/salary-releases/{id}/pdf', [SalaryReleaseApiController::class, 'pdf'])->middleware('feature:salary_releases,read');
        
        // Currencies (admin only)
        Route::get('/currencies', [CurrencyApiController::class, 'index'])->middleware('feature:currencies,read');
        Route::post('/currencies', [CurrencyApiController::class, 'store'])->middleware('feature:currencies,write');
        Route::put('/currencies/{id}', [CurrencyApiController::class, 'update'])->middleware('feature:currencies,write');
        Route::post('/currencies/{id}/set-base', [CurrencyApiController::class, 'setBase'])->middleware('feature:currencies,write');
        Route::post('/currencies/{id}/toggle-active', [CurrencyApiController::class, 'toggleActive'])->middleware('feature:currencies,write');
        Route::delete('/currencies/{id}', [CurrencyApiController::class, 'destroy'])->middleware('feature:currencies,write');
    });
});
