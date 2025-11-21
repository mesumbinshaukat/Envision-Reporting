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
        
        // Employees (admin only)
        Route::apiResource('employees', EmployeeApiController::class);
        Route::post('/employees/{id}/toggle-geolocation', [EmployeeApiController::class, 'toggleGeolocation']);
        Route::post('/employees/bulk-action', [EmployeeApiController::class, 'bulkAction']);
        
        // Attendance
        Route::get('/attendance', [AttendanceApiController::class, 'index']);
        Route::post('/attendance/check-in', [AttendanceApiController::class, 'checkIn']);
        Route::post('/attendance/check-out', [AttendanceApiController::class, 'checkOut']);
        Route::get('/attendance/statistics', [AttendanceApiController::class, 'statistics']);
        Route::get('/attendance/{id}', [AttendanceApiController::class, 'show']);
        
        // Clients
        Route::apiResource('clients', ClientApiController::class);
        
        // Invoices
        Route::apiResource('invoices', InvoiceApiController::class);
        Route::get('/invoices/{id}/pdf', [InvoiceApiController::class, 'pdf']);
        Route::post('/invoices/{id}/approve', [InvoiceApiController::class, 'approve']);
        Route::post('/invoices/{id}/reject', [InvoiceApiController::class, 'reject']);
        
        // Expenses (admin only)
        Route::apiResource('expenses', ExpenseApiController::class);
        
        // Bonuses (admin only)
        Route::apiResource('bonuses', BonusApiController::class);
        
        // Salary Releases (admin only)
        Route::apiResource('salary-releases', SalaryReleaseApiController::class);
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
