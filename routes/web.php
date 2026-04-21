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
use App\Http\Controllers\AllowanceTypeController;
use App\Http\Controllers\EmployeeAllowanceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AttendanceFixRequestController;
use App\Http\Controllers\AttendanceLogController;
use App\Http\Controllers\OfficeLocationController;
use App\Http\Controllers\OfficeScheduleController;
use App\Http\Controllers\OfficeClosureController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\EmployeeIpWhitelistController;
use App\Http\Controllers\EmployeeActivityLogController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/main', function () {
    return redirect()->route('dashboard');
})->middleware('auth.both')->name('main');

// Routes accessible by both admin and employee
Route::middleware(['auth.both', 'log.employee.activity'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Calibration data - accessible by employees for check-in
    Route::get('/office-location/calibration', [OfficeLocationController::class, 'getCalibration'])->name('office-location.calibration');
    
    // Invoices - accessible by both, but feature-permissions apply to web users (admins/moderators/supervisors)
    // Invoices routes - Write routes before read routes to avoid wildcard conflicts
    Route::middleware('feature:invoices,write')->group(function () {
        Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
        Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
        Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
        Route::put('/invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('invoices.pay');
        Route::post('/invoices/{invoice}/restore', [InvoiceController::class, 'restore'])->name('invoices.restore');
        Route::delete('/invoices/{invoice}/force-delete', [InvoiceController::class, 'forceDelete'])->name('invoices.force-delete');
        Route::post('/invoices/{invoice}/approve', [InvoiceController::class, 'approve'])->name('invoices.approve');
        Route::post('/invoices/{invoice}/reject', [InvoiceController::class, 'reject'])->name('invoices.reject');
    });
    Route::middleware('feature:invoices,read')->group(function () {
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
        Route::get('/invoices/trash/index', [InvoiceController::class, 'trash'])->name('invoices.trash');
    });
    
    // Clients routes - Write routes before read routes to avoid wildcard conflicts
    Route::middleware('feature:clients,write')->group(function () {
        Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
        Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
        Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
        Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
        Route::post('/clients/{client}/restore', [ClientController::class, 'restore'])->name('clients.restore');
        Route::delete('/clients/{client}/force-delete', [ClientController::class, 'forceDelete'])->name('clients.force-delete');
    });
    Route::middleware('feature:clients,read')->group(function () {
        Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
        Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');
        Route::get('/clients/trash/index', [ClientController::class, 'trash'])->name('clients.trash');
    });
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Employee-only routes (attendance)
Route::middleware(['auth.both', 'employee', 'log.employee.activity'])->group(function () {
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
Route::middleware(['auth.both', 'admin', 'log.employee.activity'])->group(function () {
    // Employee routes - Order matters! Specific routes must come before wildcards
    // Write routes (create, edit) must be registered before read routes (show) to avoid 'create' being caught by {employee}
    Route::middleware('feature:employees,write')->group(function () {
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
        Route::post('/employees/{employee}/toggle-geolocation', [EmployeeController::class, 'toggleGeolocation'])->name('employees.toggle-geolocation');
        Route::post('/employees/bulk/action', [EmployeeController::class, 'bulkAction'])->name('employees.bulk-action');
        Route::post('/employees/bulk/update', [EmployeeController::class, 'bulkUpdate'])->name('employees.bulk-update');
        Route::post('/employees/{employee}/employee-user', [EmployeeUserController::class, 'store'])->name('employee-users.store');
        Route::delete('/employee-users/{employeeUser}', [EmployeeUserController::class, 'destroy'])->name('employee-users.destroy');
        Route::delete('/employees/{employee}/full-delete', [EmployeeUserController::class, 'destroyFull'])->name('employees.full-delete');
    });
    Route::middleware('feature:employees,read')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::post('/employees/bulk/fetch', [EmployeeController::class, 'bulkFetch'])->name('employees.bulk-fetch');
        // Salary slip route - must come after all specific routes to avoid conflicts
        Route::get('/employees/{employee}/salary-slip', [EmployeeController::class, 'salarySlip'])->name('employees.salary-slip');
    });
    
    // Expenses routes - Write routes before read routes to avoid wildcard conflicts
    Route::middleware('feature:expenses,write')->group(function () {
        Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
    });
    Route::middleware('feature:expenses,read')->group(function () {
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('/expenses/{expense}', [ExpenseController::class, 'show'])->name('expenses.show');
    });

    // Allowances routes - Write routes before read routes to avoid wildcard conflicts
    Route::middleware('feature:allowances,write')->group(function () {
        // Allowance types management
        Route::get('/allowance-types/create', [AllowanceTypeController::class, 'create'])->name('allowance-types.create');
        Route::post('/allowance-types', [AllowanceTypeController::class, 'store'])->name('allowance-types.store');
        Route::get('/allowance-types/{allowanceType}/edit', [AllowanceTypeController::class, 'edit'])->name('allowance-types.edit');
        Route::put('/allowance-types/{allowanceType}', [AllowanceTypeController::class, 'update'])->name('allowance-types.update');
        Route::delete('/allowance-types/{allowanceType}', [AllowanceTypeController::class, 'destroy'])->name('allowance-types.destroy');
        
        // Employee allowances
        Route::get('/employee-allowances/create', [EmployeeAllowanceController::class, 'create'])->name('employee-allowances.create');
        Route::post('/employee-allowances', [EmployeeAllowanceController::class, 'store'])->name('employee-allowances.store');
        Route::get('/employee-allowances/{employeeAllowance}/edit', [EmployeeAllowanceController::class, 'edit'])->name('employee-allowances.edit');
        Route::put('/employee-allowances/{employeeAllowance}', [EmployeeAllowanceController::class, 'update'])->name('employee-allowances.update');
        Route::delete('/employee-allowances/{employeeAllowance}', [EmployeeAllowanceController::class, 'destroy'])->name('employee-allowances.destroy');
    });
    Route::middleware('feature:allowances,read')->group(function () {
        Route::get('/allowance-types', [AllowanceTypeController::class, 'index'])->name('allowance-types.index');
        Route::get('/employee-allowances', [EmployeeAllowanceController::class, 'index'])->name('employee-allowances.index');
    });

    // Bonuses routes - Write routes before read routes to avoid wildcard conflicts
    Route::middleware('feature:bonuses,write')->group(function () {
        Route::get('/bonuses/create', [BonusController::class, 'create'])->name('bonuses.create');
        Route::post('/bonuses', [BonusController::class, 'store'])->name('bonuses.store');
        Route::get('/bonuses/{bonus}/edit', [BonusController::class, 'edit'])->name('bonuses.edit');
        Route::put('/bonuses/{bonus}', [BonusController::class, 'update'])->name('bonuses.update');
        Route::delete('/bonuses/{bonus}', [BonusController::class, 'destroy'])->name('bonuses.destroy');
    });
    Route::middleware('feature:bonuses,read')->group(function () {
        Route::get('/bonuses', [BonusController::class, 'index'])->name('bonuses.index');
        Route::get('/bonuses/{bonus}', [BonusController::class, 'show'])->name('bonuses.show');
    });
    
    // Salary releases routes - Write routes before read routes to avoid wildcard conflicts
    Route::middleware('feature:salary_releases,write')->group(function () {
        Route::get('/salary-releases/create', [SalaryReleaseController::class, 'create'])->name('salary-releases.create');
        Route::post('/salary-releases', [SalaryReleaseController::class, 'store'])->name('salary-releases.store');
        Route::get('/salary-releases/{salaryRelease}/edit', [SalaryReleaseController::class, 'edit'])->name('salary-releases.edit');
        Route::put('/salary-releases/{salaryRelease}', [SalaryReleaseController::class, 'update'])->name('salary-releases.update');
        Route::delete('/salary-releases/{salaryRelease}', [SalaryReleaseController::class, 'destroy'])->name('salary-releases.destroy');
        Route::post('/salary-releases/preview', [SalaryReleaseController::class, 'preview'])->name('salary-releases.preview');
    });
    Route::middleware('feature:salary_releases,read')->group(function () {
        Route::get('/salary-releases', [SalaryReleaseController::class, 'index'])->name('salary-releases.index');
        Route::get('/salary-releases/{salaryRelease}', [SalaryReleaseController::class, 'show'])->name('salary-releases.show');
        Route::get('/salary-releases/{salaryRelease}/pdf', [SalaryReleaseController::class, 'pdf'])->name('salary-releases.pdf');
    });
    
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index')->middleware('feature:reports,read');
    Route::post('/reports/audit', [ReportController::class, 'audit'])->name('reports.audit')->middleware('feature:reports,read');
    
    // Admin attendance management
    Route::prefix('admin/attendance')->name('admin.attendance.')->group(function () {
        Route::get('/', [AdminAttendanceController::class, 'index'])->name('index')->middleware('feature:attendance_admin,read');
        Route::get('/create', [AdminAttendanceController::class, 'create'])->name('create')->middleware('feature:attendance_admin,write');
        Route::post('/', [AdminAttendanceController::class, 'store'])->name('store')->middleware('feature:attendance_admin,write');
        Route::get('/statistics/view', [AdminAttendanceController::class, 'statistics'])->name('statistics')->middleware('feature:attendance_admin,read');

        Route::get('/office-schedule', [OfficeScheduleController::class, 'edit'])->name('office-schedule.edit')->middleware('feature:attendance_admin,read');
        Route::put('/office-schedule', [OfficeScheduleController::class, 'update'])->name('office-schedule.update')->middleware('feature:attendance_admin,write');

        Route::get('/closures', [OfficeClosureController::class, 'index'])->name('closures.index')->middleware('feature:attendance_admin,read');
        Route::post('/closures', [OfficeClosureController::class, 'store'])->name('closures.store')->middleware('feature:attendance_admin,write');
        Route::delete('/closures/{closure}', [OfficeClosureController::class, 'destroy'])->name('closures.destroy')->middleware('feature:attendance_admin,write');

        Route::get('/ip-whitelists', [EmployeeIpWhitelistController::class, 'index'])->name('ip-whitelists.index')->middleware('feature:attendance_admin,read');
        Route::post('/ip-whitelists', [EmployeeIpWhitelistController::class, 'store'])->name('ip-whitelists.store')->middleware('feature:attendance_admin,write');
        Route::delete('/ip-whitelists/{ipWhitelist}', [EmployeeIpWhitelistController::class, 'destroy'])->name('ip-whitelists.destroy')->middleware('feature:attendance_admin,write');
        
        // Fix request routes (must come before wildcard routes)
        Route::prefix('fix-requests')->name('fix-requests.')->group(function () {
            Route::get('/', [AttendanceFixRequestController::class, 'adminIndex'])->name('index')->middleware('feature:attendance_admin,read');
            Route::get('/{fixRequest}', [AttendanceFixRequestController::class, 'adminShow'])->name('show')->middleware('feature:attendance_admin,read');
            Route::post('/{fixRequest}/process', [AttendanceFixRequestController::class, 'process'])->name('process')->middleware('feature:attendance_admin,write');
            Route::get('/{fixRequest}/edit-attendance', [AttendanceFixRequestController::class, 'editAttendance'])->name('edit-attendance')->middleware('feature:attendance_admin,write');
        });
        
        // Wildcard routes (must come last)
        Route::get('/{attendance}', [AdminAttendanceController::class, 'show'])->name('show')->middleware('feature:attendance_admin,read');
        Route::get('/{attendance}/edit', [AdminAttendanceController::class, 'edit'])->name('edit')->middleware('feature:attendance_admin,write');
        Route::put('/{attendance}', [AdminAttendanceController::class, 'update'])->name('update')->middleware('feature:attendance_admin,write');
        Route::delete('/{attendance}', [AdminAttendanceController::class, 'destroy'])->name('destroy')->middleware('feature:attendance_admin,write');
    });
    
    // Currency management (admin only)
    Route::get('/currencies', [CurrencyController::class, 'index'])->name('currencies.index')->middleware('feature:currencies,read');
    Route::post('/currencies', [CurrencyController::class, 'store'])->name('currencies.store')->middleware('feature:currencies,write');
    Route::put('/currencies/{currency}', [CurrencyController::class, 'update'])->name('currencies.update')->middleware('feature:currencies,write');
    Route::post('/currencies/{currency}/set-base', [CurrencyController::class, 'setBase'])->name('currencies.set-base')->middleware('feature:currencies,write');
    Route::post('/currencies/{currency}/toggle-active', [CurrencyController::class, 'toggleActive'])->name('currencies.toggle-active')->middleware('feature:currencies,write');
    Route::delete('/currencies/{currency}', [CurrencyController::class, 'destroy'])->name('currencies.destroy')->middleware('feature:currencies,write');
    
    // Office Location Settings (admin only)
    Route::prefix('admin/office-location')->name('admin.office-location.')->group(function () {
        Route::get('/', [OfficeLocationController::class, 'index'])->name('index')->middleware('feature:office_location,read');
        Route::post('/', [OfficeLocationController::class, 'update'])->name('update')->middleware('feature:office_location,write');
        Route::get('/calibration', [OfficeLocationController::class, 'getCalibration'])->name('calibration')->middleware('feature:office_location,read');
        Route::post('/current-location', [OfficeLocationController::class, 'getCurrentLocation'])->name('current-location')->middleware('feature:office_location,read');
        Route::post('/toggle-enforcement', [OfficeLocationController::class, 'toggleEnforcement'])->name('toggle-enforcement')->middleware('feature:office_location,write');
        Route::post('/toggle-ip-whitelist', [OfficeLocationController::class, 'toggleIpWhitelist'])->name('toggle-ip-whitelist')->middleware('feature:office_location,write');
    });
    
    // Attendance Logs (admin only)
    Route::prefix('admin/attendance-logs')->name('admin.attendance-logs.')->group(function () {
        Route::get('/', [AttendanceLogController::class, 'index'])->name('index')->middleware('feature:attendance_logs,read');
        Route::post('/cleanup', [AttendanceLogController::class, 'cleanup'])->name('cleanup')->middleware('feature:attendance_logs,write');
        Route::get('/{log}', [AttendanceLogController::class, 'show'])->name('show')->middleware('feature:attendance_logs,read');
    });

    // Employee Activity Logs
    Route::prefix('admin/activity-logs')->name('admin.activity-logs.')->group(function () {
        Route::get('/', [EmployeeActivityLogController::class, 'index'])->name('index')->middleware('feature:activity_logs,read');
        Route::post('/cleanup', [EmployeeActivityLogController::class, 'cleanup'])->name('cleanup')->middleware('feature:activity_logs,write');
        Route::get('/{log}', [EmployeeActivityLogController::class, 'show'])->name('show')->middleware('feature:activity_logs,read');
    });

    // User management (admin role should grant this feature; can be delegated)
    Route::prefix('admin/users')->name('admin.users.')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('index')->middleware('feature:users,read');
        Route::get('/create', [UserManagementController::class, 'create'])->name('create')->middleware('feature:users,write');
        Route::post('/', [UserManagementController::class, 'store'])->name('store')->middleware('feature:users,write');
        Route::get('/{user}/edit', [UserManagementController::class, 'edit'])->name('edit')->middleware('feature:users,write');
        Route::put('/{user}', [UserManagementController::class, 'update'])->name('update')->middleware('feature:users,write');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy')->middleware('feature:users,write');
        Route::get('/{user}', [UserManagementController::class, 'show'])->name('show')->middleware('feature:users,read');
    });
});

require __DIR__.'/auth.php';
