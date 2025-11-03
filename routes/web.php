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
use App\Http\Controllers\CurrencyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Routes accessible by both admin and employee
Route::middleware(['auth.both'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
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

// Admin-only routes
Route::middleware(['auth.both', 'admin'])->group(function () {
    Route::resource('employees', EmployeeController::class);
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
    
    // Currency management (admin only)
    Route::get('/currencies', [CurrencyController::class, 'index'])->name('currencies.index');
    Route::post('/currencies', [CurrencyController::class, 'store'])->name('currencies.store');
    Route::put('/currencies/{currency}', [CurrencyController::class, 'update'])->name('currencies.update');
    Route::post('/currencies/{currency}/set-base', [CurrencyController::class, 'setBase'])->name('currencies.set-base');
    Route::post('/currencies/{currency}/toggle-active', [CurrencyController::class, 'toggleActive'])->name('currencies.toggle-active');
    Route::delete('/currencies/{currency}', [CurrencyController::class, 'destroy'])->name('currencies.destroy');
});

require __DIR__.'/auth.php';
