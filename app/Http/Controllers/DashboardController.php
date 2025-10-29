<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Expense;
use App\Models\SalaryRelease;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class DashboardController extends Controller
{
    use AuthorizesRequests;
    public function index()
    {
        $user = auth()->user();
        
        $stats = [
            'total_clients' => $user->clients()->count(),
            'total_employees' => $user->employees()->count(),
            'pending_invoices' => $user->invoices()->where('status', 'Pending')->count(),
            'total_expenses' => $user->expenses()->sum('amount'),
            'recent_invoices' => $user->invoices()->with(['client', 'employee'])->latest()->take(5)->get(),
            'recent_expenses' => $user->expenses()->latest()->take(5)->get(),
        ];
        
        return view('dashboard', $stats);
    }
}
