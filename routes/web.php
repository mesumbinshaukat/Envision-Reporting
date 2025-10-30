<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\BonusController;
use App\Http\Controllers\SalaryReleaseController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    Route::resource('clients', ClientController::class);
    Route::resource('employees', EmployeeController::class);
    Route::resource('invoices', InvoiceController::class);
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::put('/invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('invoices.pay');
    
    Route::resource('expenses', ExpenseController::class);
    Route::resource('bonuses', BonusController::class);
    
    Route::resource('salary-releases', SalaryReleaseController::class);
    Route::post('/salary-releases/preview', [SalaryReleaseController::class, 'preview'])->name('salary-releases.preview');
    Route::get('/salary-releases/{salaryRelease}/pdf', [SalaryReleaseController::class, 'pdf'])->name('salary-releases.pdf');
    
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports/audit', [ReportController::class, 'audit'])->name('reports.audit');
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
