<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeUserController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\BonusController;
use App\Http\Controllers\SalaryReleaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AttendanceFixRequestController;
use App\Http\Controllers\AttendanceLogController;
use App\Http\Controllers\OfficeLocationController;
use App\Http\Controllers\CurrencyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/main', function () {
    return redirect()->route('dashboard');
})->middleware('auth.both')->name('main');

// Routes accessible by both admin and employee
Route::middleware(['auth.both'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Calibration data - accessible by employees for check-in
    Route::get('/office-location/calibration', [OfficeLocationController::class, 'getCalibration'])->name('office-location.calibration');
    
    // Invoices - accessible by both but with different permissions
    Route::resource('invoices', InvoiceController::class);
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::put('/invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('invoices.pay');
    Route::get('/invoices/trash/index', [InvoiceController::class, 'trash'])->name('invoices.trash');
    Route::post('/invoices/{invoice}/restore', [InvoiceController::class, 'restore'])->name('invoices.restore');
    Route::delete('/invoices/{invoice}/force-delete', [InvoiceController::class, 'forceDelete'])->name('invoices.force-delete');
    Route::post('/invoices/{invoice}/approve', [InvoiceController::class, 'approve'])->name('invoices.approve');
    Route::post('/invoices/{invoice}/reject', [InvoiceController::class, 'reject'])->name('invoices.reject');
    
    // Clients - accessible by both
    Route::resource('clients', ClientController::class);
    Route::get('/clients/trash/index', [ClientController::class, 'trash'])->name('clients.trash');
    Route::post('/clients/{client}/restore', [ClientController::class, 'restore'])->name('clients.restore');
    Route::delete('/clients/{client}/force-delete', [ClientController::class, 'forceDelete'])->name('clients.force-delete');
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Employee-only routes (attendance)
Route::middleware(['auth.both', 'employee'])->group(function () {
    // Employee attendance
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.check-in');
    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.check-out');
    
    // Employee fix requests (must come before wildcard routes)
    Route::get('/attendance/fix-requests', [AttendanceFixRequestController::class, 'index'])->name('attendance.fix-requests.index');
    Route::post('/attendance/fix-requests', [AttendanceFixRequestController::class, 'store'])->name('attendance.fix-requests.store');
    Route::get('/attendance/fix-requests/{fixRequest}', [AttendanceFixRequestController::class, 'show'])->name('attendance.fix-requests.show');
    
    // Wildcard routes (must come last)
    Route::get('/attendance/{attendance}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::get('/attendance/{attendance}/fix-request/create', [AttendanceFixRequestController::class, 'create'])->name('attendance.fix-requests.create');
});

// Admin-only routes
Route::middleware(['auth.both', 'admin'])->group(function () {
    Route::resource('employees', EmployeeController::class);
    Route::post('/employees/{employee}/toggle-geolocation', [EmployeeController::class, 'toggleGeolocation'])->name('employees.toggle-geolocation');
    Route::post('/employees/{employee}/employee-user', [EmployeeUserController::class, 'store'])->name('employee-users.store');
    Route::delete('/employee-users/{employeeUser}', [EmployeeUserController::class, 'destroy'])->name('employee-users.destroy');
    Route::delete('/employees/{employee}/full-delete', [EmployeeUserController::class, 'destroyFull'])->name('employees.full-delete');
    
    Route::resource('expenses', ExpenseController::class);
    Route::resource('bonuses', BonusController::class);
    
    Route::resource('salary-releases', SalaryReleaseController::class);
    Route::post('/salary-releases/preview', [SalaryReleaseController::class, 'preview'])->name('salary-releases.preview');
    Route::get('/salary-releases/{salaryRelease}/pdf', [SalaryReleaseController::class, 'pdf'])->name('salary-releases.pdf');
    
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports/audit', [ReportController::class, 'audit'])->name('reports.audit');
    
    // Admin attendance management
    Route::prefix('admin/attendance')->name('admin.attendance.')->group(function () {
        Route::get('/', [AdminAttendanceController::class, 'index'])->name('index');
        Route::get('/create', [AdminAttendanceController::class, 'create'])->name('create');
        Route::post('/', [AdminAttendanceController::class, 'store'])->name('store');
        Route::get('/statistics/view', [AdminAttendanceController::class, 'statistics'])->name('statistics');
        
        // Fix request routes (must come before wildcard routes)
        Route::prefix('fix-requests')->name('fix-requests.')->group(function () {
            Route::get('/', [AttendanceFixRequestController::class, 'adminIndex'])->name('index');
            Route::get('/{fixRequest}', [AttendanceFixRequestController::class, 'adminShow'])->name('show');
            Route::post('/{fixRequest}/process', [AttendanceFixRequestController::class, 'process'])->name('process');
            Route::get('/{fixRequest}/edit-attendance', [AttendanceFixRequestController::class, 'editAttendance'])->name('edit-attendance');
        });
        
        // Wildcard routes (must come last)
        Route::get('/{attendance}', [AdminAttendanceController::class, 'show'])->name('show');
        Route::get('/{attendance}/edit', [AdminAttendanceController::class, 'edit'])->name('edit');
        Route::put('/{attendance}', [AdminAttendanceController::class, 'update'])->name('update');
        Route::delete('/{attendance}', [AdminAttendanceController::class, 'destroy'])->name('destroy');
    });
    
    // Currency management (admin only)
    Route::get('/currencies', [CurrencyController::class, 'index'])->name('currencies.index');
    Route::post('/currencies', [CurrencyController::class, 'store'])->name('currencies.store');
    Route::put('/currencies/{currency}', [CurrencyController::class, 'update'])->name('currencies.update');
    Route::post('/currencies/{currency}/set-base', [CurrencyController::class, 'setBase'])->name('currencies.set-base');
    Route::post('/currencies/{currency}/toggle-active', [CurrencyController::class, 'toggleActive'])->name('currencies.toggle-active');
    Route::delete('/currencies/{currency}', [CurrencyController::class, 'destroy'])->name('currencies.destroy');
    
    // Office Location Settings (admin only)
    Route::prefix('admin/office-location')->name('admin.office-location.')->group(function () {
        Route::get('/', [OfficeLocationController::class, 'index'])->name('index');
        Route::post('/', [OfficeLocationController::class, 'update'])->name('update');
        Route::get('/calibration', [OfficeLocationController::class, 'getCalibration'])->name('calibration');
        Route::post('/current-location', [OfficeLocationController::class, 'getCurrentLocation'])->name('current-location');
    });
    
    // Attendance Logs (admin only)
    Route::prefix('admin/attendance-logs')->name('admin.attendance-logs.')->group(function () {
        Route::get('/', [AttendanceLogController::class, 'index'])->name('index');
        Route::get('/{log}', [AttendanceLogController::class, 'show'])->name('show');
    });
});

require __DIR__.'/auth.php';
