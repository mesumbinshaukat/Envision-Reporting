<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ReportController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request)
    {
        $user = auth()->user();
        $reportData = null;
        
        if ($request->has('date_from') && $request->has('date_to')) {
            $validated = $request->validate([
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from',
            ]);
            
            // Get invoices that have payments in the selected date range
            $invoices = $user->invoices()
                ->with(['client', 'employee', 'payments'])
                ->whereHas('payments', function($query) use ($validated) {
                    $query->whereBetween('payment_date', [$validated['date_from'], $validated['date_to']]);
                })
                ->get();
            
            // Filter payments within the date range for each invoice
            $invoices->each(function($invoice) use ($validated) {
                $invoice->setRelation('payments', 
                    $invoice->payments->filter(function($payment) use ($validated) {
                        return $payment->payment_date >= $validated['date_from'] 
                            && $payment->payment_date <= $validated['date_to'];
                    })
                );
            });
            
            $expenses = $user->expenses()
                ->whereBetween('date', [$validated['date_from'], $validated['date_to']])
                ->get();
            
            $salaryReleases = $user->salaryReleases()
                ->with('employee')
                ->whereBetween('release_date', [$validated['date_from'], $validated['date_to']])
                ->get();
            
            $bonuses = $user->bonuses()
                ->with('employee')
                ->whereBetween('date', [$validated['date_from'], $validated['date_to']])
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
        
        $user = auth()->user();
        
        // Get invoices that have payments in the selected date range
        $invoices = $user->invoices()
            ->with(['client', 'employee', 'payments'])
            ->whereHas('payments', function($query) use ($validated) {
                $query->whereBetween('payment_date', [$validated['date_from'], $validated['date_to']]);
            })
            ->get();
        
        // Filter payments within the date range for each invoice
        $invoices->each(function($invoice) use ($validated) {
            $invoice->setRelation('payments', 
                $invoice->payments->filter(function($payment) use ($validated) {
                    return $payment->payment_date >= $validated['date_from'] 
                        && $payment->payment_date <= $validated['date_to'];
                })
            );
        });
        
        $expenses = $user->expenses()
            ->whereBetween('date', [$validated['date_from'], $validated['date_to']])
            ->get();
        
        $salaryReleases = $user->salaryReleases()
            ->with('employee')
            ->whereBetween('release_date', [$validated['date_from'], $validated['date_to']])
            ->get();
        
        $bonuses = $user->bonuses()
            ->with('employee')
            ->whereBetween('date', [$validated['date_from'], $validated['date_to']])
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
