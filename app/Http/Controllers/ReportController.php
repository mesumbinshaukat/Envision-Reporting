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
            
            $invoices = $user->invoices()
                ->with(['client', 'employee'])
                ->whereBetween('created_at', [$validated['date_from'], $validated['date_to']])
                ->get();
            
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
            
            // Separate paid and unpaid invoices
            $paidInvoices = $invoices->where('status', 'Payment Done');
            $unpaidInvoices = $invoices->whereIn('status', ['Pending', 'Partial Paid']);
            
            $reportData = [
                'date_from' => $validated['date_from'],
                'date_to' => $validated['date_to'],
                'invoices' => $invoices,
                'paid_invoices' => $paidInvoices,
                'unpaid_invoices' => $unpaidInvoices,
                'expenses' => $expenses,
                'salaryReleases' => $salaryReleases,
                'bonuses' => $bonuses,
                'total_invoices' => $invoices->sum('amount'),
                'total_paid_invoices' => $paidInvoices->sum('amount'),
                'total_unpaid_invoices' => $unpaidInvoices->sum('amount'),
                'total_expenses' => $expenses->sum('amount'),
                'total_salaries' => $salaryReleases->sum('total_amount'),
                'total_bonuses' => $bonuses->sum('amount'),
                // Net Income = ONLY Paid Invoices - Expenses - Salaries (unpaid invoices excluded)
                'net_income' => $paidInvoices->sum('amount') - $expenses->sum('amount') - $salaryReleases->sum('total_amount'),
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
        
        $invoices = $user->invoices()
            ->with(['client', 'employee'])
            ->whereBetween('created_at', [$validated['date_from'], $validated['date_to']])
            ->get();
        
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
        
        // Separate paid and unpaid invoices
        $paidInvoices = $invoices->where('status', 'Payment Done');
        $unpaidInvoices = $invoices->whereIn('status', ['Pending', 'Partial Paid']);
        
        $data = [
            'user' => $user,
            'date_from' => $validated['date_from'],
            'date_to' => $validated['date_to'],
            'invoices' => $invoices,
            'paid_invoices' => $paidInvoices,
            'unpaid_invoices' => $unpaidInvoices,
            'expenses' => $expenses,
            'salaryReleases' => $salaryReleases,
            'bonuses' => $bonuses,
            'total_invoices' => $invoices->sum('amount'),
            'total_paid_invoices' => $paidInvoices->sum('amount'),
            'total_unpaid_invoices' => $unpaidInvoices->sum('amount'),
            'total_expenses' => $expenses->sum('amount'),
            'total_salaries' => $salaryReleases->sum('total_amount'),
            'total_bonuses' => $bonuses->sum('amount'),
            // Net Income = ONLY Paid Invoices - Expenses - Salaries (unpaid invoices excluded)
            'net_income' => $paidInvoices->sum('amount') - $expenses->sum('amount') - $salaryReleases->sum('total_amount'),
        ];
        
        $pdf = Pdf::loadView('reports.audit-pdf', $data);
        return $pdf->download('audit-report-' . date('Y-m-d') . '.pdf');
    }
}
