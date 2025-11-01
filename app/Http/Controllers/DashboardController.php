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
        $isEmployee = auth()->guard('employee')->check();
        
        if ($isEmployee) {
            return $this->employeeDashboard();
        }
        
        return $this->adminDashboard();
    }

    private function adminDashboard()
    {
        $user = auth()->user();
        
        $stats = [
            'total_clients' => $user->clients()->count(),
            'total_employees' => $user->employees()->count(),
            'pending_invoices' => $user->invoices()->where('status', 'Pending')->count(),
            'pending_approvals' => $user->invoices()->where('approval_status', 'pending')->count(),
            'total_expenses' => $user->expenses()->sum('amount'),
            'recent_invoices' => $user->invoices()->with(['client', 'employee'])->latest()->take(5)->get(),
            'recent_expenses' => $user->expenses()->latest()->take(5)->get(),
        ];
        
        return view('dashboard', $stats);
    }

    private function employeeDashboard()
    {
        $employeeUser = auth()->guard('employee')->user();
        $employee = $employeeUser->employee;
        
        // Get invoices where this employee is the salesperson
        $employeeInvoices = Invoice::where('employee_id', $employee->id)
            ->where('approval_status', 'approved')
            ->get();
        
        // Get invoices created by this employee user
        $createdInvoices = Invoice::where('created_by_employee_id', $employeeUser->id)
            ->get();
        
        // Calculate total commission earned
        $totalCommissionPaid = \App\Models\Payment::whereHas('invoice', function($q) use ($employee) {
            $q->where('employee_id', $employee->id);
        })->where('commission_paid', true)->get()->sum(function($payment) use ($employee) {
            $invoice = $payment->invoice;
            $taxPerPayment = ($invoice->tax / $invoice->amount) * $payment->amount;
            $netAmount = $payment->amount - $taxPerPayment;
            return $netAmount * ($employee->commission_rate / 100);
        });
        
        // Calculate pending commission
        $pendingCommission = \App\Models\Payment::whereHas('invoice', function($q) use ($employee) {
            $q->where('employee_id', $employee->id);
        })->where('commission_paid', false)->get()->sum(function($payment) use ($employee) {
            $invoice = $payment->invoice;
            $taxPerPayment = ($invoice->tax / $invoice->amount) * $payment->amount;
            $netAmount = $payment->amount - $taxPerPayment;
            return $netAmount * ($employee->commission_rate / 100);
        });
        
        $stats = [
            'employee' => $employee,
            'total_commission_paid' => $totalCommissionPaid,
            'pending_commission' => $pendingCommission,
            'pending_invoices' => $createdInvoices->where('approval_status', 'pending')->count(),
            'approved_invoices' => $createdInvoices->where('approval_status', 'approved')->count(),
            'rejected_invoices' => $createdInvoices->where('approval_status', 'rejected')->count(),
            'recent_invoices' => $createdInvoices->take(5),
        ];
        
        return view('employee-dashboard', $stats);
    }
}
