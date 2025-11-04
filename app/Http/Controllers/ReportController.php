<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Invoice;
use App\Models\Expense;
use App\Models\SalaryRelease;
use App\Models\Bonus;
use Carbon\Carbon;

class ReportController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request)
    {
        $userId = auth()->id();
        $reportData = null;
        
        if ($request->has('date_from') && $request->has('date_to')) {
            $validated = $request->validate([
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from',
            ]);
            
            // Get invoices that have payments in the selected date range (inclusive)
            $dateFrom = $validated['date_from'];
            $dateTo = date('Y-m-d 23:59:59', strtotime($validated['date_to']));
            
            $invoices = Invoice::where('user_id', $userId)
                ->with(['client', 'employee', 'payments'])
                ->whereHas('payments', function($query) use ($dateFrom, $dateTo) {
                    $query->where('payment_date', '>=', $dateFrom)
                          ->where('payment_date', '<=', $dateTo);
                })
                ->get();
            
            // Filter payments within the date range for each invoice
            $invoices->each(function($invoice) use ($dateFrom, $dateTo) {
                $invoice->setRelation('payments', 
                    $invoice->payments->filter(function($payment) use ($dateFrom, $dateTo) {
                        return $payment->payment_date >= $dateFrom 
                            && $payment->payment_date <= $dateTo;
                    })
                );
            });
            
            $expenses = Expense::where('user_id', $userId)
                ->where('date', '>=', $dateFrom)
                ->where('date', '<=', $dateTo)
                ->get();
            
            $fromMonth = Carbon::parse($dateFrom)->format('Y-m');
            $toMonth = Carbon::parse($dateTo)->format('Y-m');
            if($fromMonth == $toMonth){
                $salaryReleases = SalaryRelease::where('user_id', $userId)
                ->with('employee')
                ->where('month', $fromMonth)
                ->get();
            }else{
                $salaryReleases = SalaryRelease::where('user_id', $userId)
                ->with('employee')
                ->where('release_date', '>=', $dateFrom)
                ->where('release_date', '<=', $dateTo)
                ->get();
            }
          
            
            $bonuses = Bonus::where('user_id', $userId)
                ->with('employee')
                ->where('date', '>=', $dateFrom)
                ->where('date', '<=', $dateTo)
                ->get();
            
            // Calculate total payments made in this date range
            $totalPaymentsInRange = $invoices->sum(function($invoice) {
                return $invoice->payments->sum('amount');
            });
            
            // Separate invoices by status
            $paidInvoices = $invoices->where('status', 'Payment Done');
            $partialPaidInvoices = $invoices->where('status', 'Partial Paid');
            $pendingInvoices = $invoices->where('status', 'Pending');
            
            $reportData = [
                'date_from' => $validated['date_from'],
                'date_to' => $validated['date_to'],
                'invoices' => $invoices,
                'paid_invoices' => $paidInvoices,
                'partial_paid_invoices' => $partialPaidInvoices,
                'pending_invoices' => $pendingInvoices,
                'expenses' => $expenses,
                'salaryReleases' => $salaryReleases,
                'bonuses' => $bonuses,
                'total_payments_in_range' => $totalPaymentsInRange,
                'total_invoices' => $invoices->sum('amount'),
                'total_expenses' => $expenses->sum('amount'),
                'total_salaries' => $salaryReleases->sum('total_amount'),
                'total_bonuses' => $bonuses->sum('amount'),
                // Net Income = Payments received in date range - Expenses - Salaries
                'net_income' => $totalPaymentsInRange - $expenses->sum('amount') - $salaryReleases->sum('total_amount'),
            ];
        }
        
        return view('reports.index', compact('reportData'));
    }

    public function audit(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);
        
        $userId = auth()->id();
        
        // Get invoices that have payments in the selected date range (inclusive)
        $dateFrom = $validated['date_from'];
        $dateTo = date('Y-m-d 23:59:59', strtotime($validated['date_to']));
        
        $invoices = Invoice::where('user_id', $userId)
            ->with(['client', 'employee', 'payments'])
            ->whereHas('payments', function($query) use ($dateFrom, $dateTo) {
                $query->where('payment_date', '>=', $dateFrom)
                      ->where('payment_date', '<=', $dateTo);
            })
            ->get();
        
        // Filter payments within the date range for each invoice
        $invoices->each(function($invoice) use ($dateFrom, $dateTo) {
            $invoice->setRelation('payments', 
                $invoice->payments->filter(function($payment) use ($dateFrom, $dateTo) {
                    return $payment->payment_date >= $dateFrom 
                        && $payment->payment_date <= $dateTo;
                })
            );
        });
        
        $expenses = $user->expenses()
            ->where('date', '>=', $dateFrom)
            ->where('date', '<=', $dateTo)
            ->get();
        
        $salaryReleases = $user->salaryReleases()
            ->with('employee')
            ->where('release_date', '>=', $dateFrom)
            ->where('release_date', '<=', $dateTo)
            ->get();
        
        $bonuses = $user->bonuses()
            ->with('employee')
            ->where('date', '>=', $dateFrom)
            ->where('date', '<=', $dateTo)
            ->get();
        
        // Calculate total payments made in this date range
        $totalPaymentsInRange = $invoices->sum(function($invoice) {
            return $invoice->payments->sum('amount');
        });
        
        // Separate invoices by status
        $paidInvoices = $invoices->where('status', 'Payment Done');
        $partialPaidInvoices = $invoices->where('status', 'Partial Paid');
        $pendingInvoices = $invoices->where('status', 'Pending');
        
        $data = [
            'user' => $user,
            'date_from' => $validated['date_from'],
            'date_to' => $validated['date_to'],
            'invoices' => $invoices,
            'paid_invoices' => $paidInvoices,
            'partial_paid_invoices' => $partialPaidInvoices,
            'pending_invoices' => $pendingInvoices,
            'expenses' => $expenses,
            'salaryReleases' => $salaryReleases,
            'bonuses' => $bonuses,
            'total_payments_in_range' => $totalPaymentsInRange,
            'total_invoices' => $invoices->sum('amount'),
            'total_expenses' => $expenses->sum('amount'),
            'total_salaries' => $salaryReleases->sum('total_amount'),
            'total_bonuses' => $bonuses->sum('amount'),
            // Net Income = Payments received in date range - Expenses - Salaries
            'net_income' => $totalPaymentsInRange - $expenses->sum('amount') - $salaryReleases->sum('total_amount'),
        ];
        
        $pdf = Pdf::loadView('reports.audit-pdf', $data);
        return $pdf->download('audit-report-' . date('Y-m-d') . '.pdf');
    }
}
