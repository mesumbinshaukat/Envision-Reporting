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
        Route::get('/employees', [EmployeeApiController::class, 'index']);
        Route::post('/employees', [EmployeeApiController::class, 'store']);
        Route::get('/employees/{id}', [EmployeeApiController::class, 'show']);
        Route::put('/employees/{id}', [EmployeeApiController::class, 'update']);
        Route::patch('/employees/{id}', [EmployeeApiController::class, 'update']);
        Route::delete('/employees/{id}', [EmployeeApiController::class, 'destroy']);
        Route::post('/employees/{id}/toggle-geolocation', [EmployeeApiController::class, 'toggleGeolocation']);
        Route::post('/employees/bulk-action', [EmployeeApiController::class, 'bulkAction']);
        
        // Attendance
        Route::get('/attendance', [AttendanceApiController::class, 'index']);
        Route::post('/attendance/check-in', [AttendanceApiController::class, 'checkIn']);
        Route::post('/attendance/check-out', [AttendanceApiController::class, 'checkOut']);
        Route::get('/attendance/status', [AttendanceApiController::class, 'getCurrentStatus']);
        Route::get('/attendance/statistics', [AttendanceApiController::class, 'statistics']);
        Route::get('/attendance/{id}', [AttendanceApiController::class, 'show']);
        
        // Office Schedule
        Route::get('/office-schedule', [OfficeScheduleApiController::class, 'getSchedule']);
        Route::put('/office-schedule', [OfficeScheduleApiController::class, 'updateSchedule']);
        
        // Office Closures
        Route::get('/office-closures', [OfficeClosureApiController::class, 'index']);
        Route::post('/office-closures', [OfficeClosureApiController::class, 'store']);
        Route::get('/office-closures/{id}', [OfficeClosureApiController::class, 'show']);
        Route::delete('/office-closures/{id}', [OfficeClosureApiController::class, 'destroy']);
        
        // Attendance Logs (Admin only)
        Route::get('/attendance-logs', [AttendanceLogApiController::class, 'index']);
        Route::get('/attendance-logs/{id}', [AttendanceLogApiController::class, 'show']);
        Route::post('/attendance-logs/cleanup', [AttendanceLogApiController::class, 'cleanup']);
        
        // Activity Logs (Admin only)
        Route::get('/activity-logs', [ActivityLogApiController::class, 'index']);
        Route::get('/activity-logs/{id}', [ActivityLogApiController::class, 'show']);
        Route::post('/activity-logs/cleanup', [ActivityLogApiController::class, 'cleanup']);
        
        // Fix Requests
        Route::get('/fix-requests', [FixRequestApiController::class, 'index']);
        Route::post('/fix-requests', [FixRequestApiController::class, 'store']);
        Route::get('/fix-requests/{id}', [FixRequestApiController::class, 'show']);
        Route::post('/fix-requests/{id}/process', [FixRequestApiController::class, 'process']);
        
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
        Route::get('/expenses', [ExpenseApiController::class, 'index']);
        Route::post('/expenses', [ExpenseApiController::class, 'store']);
        Route::get('/expenses/{id}', [ExpenseApiController::class, 'show']);
        Route::put('/expenses/{id}', [ExpenseApiController::class, 'update']);
        Route::patch('/expenses/{id}', [ExpenseApiController::class, 'update']);
        Route::delete('/expenses/{id}', [ExpenseApiController::class, 'destroy']);
        
        // Bonuses (admin only) - manual routes to avoid name conflicts
        Route::get('/bonuses', [BonusApiController::class, 'index']);
        Route::post('/bonuses', [BonusApiController::class, 'store']);
        Route::get('/bonuses/{id}', [BonusApiController::class, 'show']);
        Route::put('/bonuses/{id}', [BonusApiController::class, 'update']);
        Route::patch('/bonuses/{id}', [BonusApiController::class, 'update']);
        Route::delete('/bonuses/{id}', [BonusApiController::class, 'destroy']);
        
        // Salary Releases (admin only) - manual routes to avoid name conflicts
        Route::get('/salary-releases', [SalaryReleaseApiController::class, 'index']);
        Route::post('/salary-releases', [SalaryReleaseApiController::class, 'store']);
        Route::get('/salary-releases/{id}', [SalaryReleaseApiController::class, 'show']);
        Route::put('/salary-releases/{id}', [SalaryReleaseApiController::class, 'update']);
        Route::patch('/salary-releases/{id}', [SalaryReleaseApiController::class, 'update']);
        Route::delete('/salary-releases/{id}', [SalaryReleaseApiController::class, 'destroy']);
        Route::post('/salary-releases/preview', [SalaryReleaseApiController::class, 'preview']);
        Route::get('/salary-releases/{id}/pdf', [SalaryReleaseApiController::class, 'pdf']);
        
        // Currencies (admin only)
        Route::get('/currencies', [CurrencyApiController::class, 'index']);
        Route::post('/currencies', [CurrencyApiController::class, 'store']);
        Route::put('/currencies/{id}', [CurrencyApiController::class, 'update']);
        Route::post('/currencies/{id}/set-base', [CurrencyApiController::class, 'setBase']);
        Route::post('/currencies/{id}/toggle-active', [CurrencyApiController::class, 'toggleActive']);
        Route::delete('/currencies/{id}', [CurrencyApiController::class, 'destroy']);
    });
});
