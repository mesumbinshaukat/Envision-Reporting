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
                ->with(['client', 'employee', 'payments', 'currency'])
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
                ->with('currency')
                ->where('date', '>=', $dateFrom)
                ->where('date', '<=', $dateTo)
                ->get();
            
            $fromMonth = Carbon::parse($dateFrom)->format('Y-m');
            $toMonth = Carbon::parse($dateTo)->format('Y-m');
            if($fromMonth == $toMonth){
                $salaryReleases = SalaryRelease::where('user_id', $userId)
                ->with(['employee', 'currency'])
                ->where('month', $fromMonth)
                ->get();
            }else{
                $salaryReleases = SalaryRelease::where('user_id', $userId)
                ->with(['employee', 'currency'])
                ->where('release_date', '>=', $dateFrom)
                ->where('release_date', '<=', $dateTo)
                ->get();
            }
          
            
            $bonuses = Bonus::where('user_id', $userId)
                ->with(['employee', 'currency'])
                ->where('date', '>=', $dateFrom)
                ->where('date', '<=', $dateTo)
                ->get();
            
            // Calculate total payments made in this date range (converted to base currency)
            $totalPaymentsInRange = $invoices->sum(function($invoice) {
                return $invoice->payments->sum(function($payment) use ($invoice) {
                    if ($invoice->currency) {
                        return $invoice->currency->toBase($payment->amount);
                    }
                    return $payment->amount;
                });
            });
            
            // Separate invoices by status
            $paidInvoices = $invoices->where('status', 'Payment Done');
            $partialPaidInvoices = $invoices->where('status', 'Partial Paid');
            $pendingInvoices = $invoices->where('status', 'Pending');
            
            // Calculate total processing fees for invoices in this date range (converted to base currency)
            $totalProcessingFees = $invoices->sum(function($invoice) {
                $fee = $invoice->payment_processing_fee ?? 0;
                if ($invoice->currency) {
                    return $invoice->currency->toBase($fee);
                }
                return $fee;
            });
            
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
                'total_payments_in_range' => $totalPaymentsInRange - $totalProcessingFees,
                'total_invoices' => $invoices->sum(function($inv) { return $inv->getAmountInBaseCurrency(); }),
                'total_processing_fees' => $totalProcessingFees,
                'total_expenses' => $expenses->sum(function($exp) { return $exp->getAmountInBaseCurrency(); }),
                'total_salaries' => $salaryReleases->sum(function($sal) { return $sal->getTotalAmountInBaseCurrency(); }),
                'total_bonuses' => $bonuses->sum(function($bon) { return $bon->getAmountInBaseCurrency(); }),
                // Net Income = Payments received in date range - Expenses - Salaries (all in base currency)
                'net_income' => $totalPaymentsInRange - $expenses->sum(function($exp) { return $exp->getAmountInBaseCurrency(); }) - $salaryReleases->sum(function($sal) { return $sal->getTotalAmountInBaseCurrency(); }) - $totalProcessingFees,
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
            ->with(['client', 'employee', 'payments', 'currency'])
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
            ->with('currency')
            ->where('date', '>=', $dateFrom)
            ->where('date', '<=', $dateTo)
            ->get();
        
        $fromMonth = Carbon::parse($dateFrom)->format('Y-m');
        $toMonth = Carbon::parse($dateTo)->format('Y-m');
        if($fromMonth == $toMonth){
            $salaryReleases = SalaryRelease::where('user_id', $userId)
            ->with(['employee', 'currency'])
            ->where('month', $fromMonth)
            ->get();
        }else{
            $salaryReleases = SalaryRelease::where('user_id', $userId)
            ->with(['employee', 'currency'])
            ->where('release_date', '>=', $dateFrom)
            ->where('release_date', '<=', $dateTo)
            ->get();
        }
        
        $bonuses = Bonus::where('user_id', $userId)
            ->with(['employee', 'currency'])
            ->where('date', '>=', $dateFrom)
            ->where('date', '<=', $dateTo)
            ->get();
        
        // Calculate total payments made in this date range (converted to base currency)
        $totalPaymentsInRange = $invoices->sum(function($invoice) {
            return $invoice->payments->sum(function($payment) use ($invoice) {
                if ($invoice->currency) {
                    return $invoice->currency->toBase($payment->amount);
                }
                return $payment->amount;
            });
        });
        
        // Separate invoices by status
        $paidInvoices = $invoices->where('status', 'Payment Done');
        $partialPaidInvoices = $invoices->where('status', 'Partial Paid');
        $pendingInvoices = $invoices->where('status', 'Pending');
        
        // Calculate total processing fees for invoices in this date range (converted to base currency)
        $totalProcessingFees = $invoices->sum(function($invoice) {
            $fee = $invoice->payment_processing_fee ?? 0;
            if ($invoice->currency) {
                return $invoice->currency->toBase($fee);
            }
            return $fee;
        });
        
        $data = [
            'user' => auth()->user(),
            'date_from' => $validated['date_from'],
            'date_to' => $validated['date_to'],
            'invoices' => $invoices,
            'paid_invoices' => $paidInvoices,
            'partial_paid_invoices' => $partialPaidInvoices,
            'pending_invoices' => $pendingInvoices,
            'expenses' => $expenses,
            'salaryReleases' => $salaryReleases,
            'bonuses' => $bonuses,
            'total_payments_in_range' => $totalPaymentsInRange - $totalProcessingFees,
            'total_invoices' => $invoices->sum(function($inv) { return $inv->getAmountInBaseCurrency(); }),
            'total_processing_fees' => $totalProcessingFees,
            'total_expenses' => $expenses->sum(function($exp) { return $exp->getAmountInBaseCurrency(); }),
            'total_salaries' => $salaryReleases->sum(function($sal) { return $sal->getTotalAmountInBaseCurrency(); }),
            'total_bonuses' => $bonuses->sum(function($bon) { return $bon->getAmountInBaseCurrency(); }),
            // Net Income = Payments received in date range - Expenses - Salaries (all in base currency)
            'net_income' => $totalPaymentsInRange - $expenses->sum(function($exp) { return $exp->getAmountInBaseCurrency(); }) - $salaryReleases->sum(function($sal) { return $sal->getTotalAmountInBaseCurrency(); }) - $totalProcessingFees,
        ];
        
        $pdf = Pdf::loadView('reports.audit-pdf', $data);
        return $pdf->download('audit-report-' . date('Y-m-d') . '.pdf');
    }
}
