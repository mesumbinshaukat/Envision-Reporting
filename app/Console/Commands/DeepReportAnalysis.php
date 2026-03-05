<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\Expense;
use App\Models\SalaryRelease;
use App\Models\Bonus;
use App\Models\User;
use App\Models\Currency;
use Carbon\Carbon;

class DeepReportAnalysis extends Command
{
    protected $signature = 'analyze:report-deep {--date_from=2026-02-01} {--date_to=2026-02-27} {--user_id=1}';
    protected $description = 'Deep analysis of report data including currency conversions';

    public function handle()
    {
        $dateFrom = $this->option('date_from');
        $dateTo = $this->option('date_to');
        $userId = $this->option('user_id');

        $this->info("=== DEEP REPORT ANALYSIS FOR USER {$userId} ===");
        $this->info("Date Range: {$dateFrom} to {$dateTo}");
        $this->newLine();

        // Get base currency
        $baseCurrency = Currency::where('is_base', true)->first();
        $this->info("Base Currency: " . ($baseCurrency ? $baseCurrency->code : 'Not found'));
        $this->newLine();

        // Get ALL invoices for this user (not just date filtered) to understand the data
        $allInvoices = Invoice::where('user_id', $userId)->with(['client', 'employee', 'currency'])->get();
        $this->info("TOTAL INVOICES FOR USER {$userId}: " . $allInvoices->count());
        
        foreach ($allInvoices as $invoice) {
            $this->line("ID: {$invoice->id}, Amount: {$invoice->amount}, Currency: " . ($invoice->currency ? $invoice->currency->code : 'NULL') . ", Invoice Date: " . ($invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : 'NULL') . ", Created: {$invoice->created_at->format('Y-m-d')}");
        }
        $this->newLine();

        // Get invoices by invoice_date in the selected date range
        $invoices = Invoice::where('user_id', $userId)
            ->with(['client', 'employee', 'payments', 'currency'])
            ->where('invoice_date', '>=', $dateFrom)
            ->where('invoice_date', '<=', $dateTo)
            ->get();

        $this->info("INVOICES IN DATE RANGE: " . $invoices->count());
        
        $totalInvoiceAmount = 0;
        $totalInvoiceAmountBase = 0;

        foreach ($invoices as $invoice) {
            $this->line("Invoice ID: {$invoice->id}");
            $this->line("  Original Amount: {$invoice->amount}");
            $this->line("  Currency: " . ($invoice->currency ? $invoice->currency->code : 'NULL'));
            $this->line("  Exchange Rate: " . ($invoice->exchange_rate_at_time ?? 'NULL'));
            
            // Calculate base currency amount
            $baseAmount = $invoice->getAmountInBaseCurrency();
            $this->line("  Base Currency Amount: {$baseAmount}");
            
            $totalInvoiceAmount += $invoice->amount;
            $totalInvoiceAmountBase += $baseAmount;
            $this->line("---");
        }

        $this->info("INVOICE TOTALS:");
        $this->info("Total Invoice Amount (Original): {$totalInvoiceAmount}");
        $this->info("Total Invoice Amount (Base Currency): {$totalInvoiceAmountBase}");
        $this->newLine();

        // Check expenses with currency conversion
        $expenses = Expense::where('user_id', $userId)
            ->with('currency')
            ->where('date', '>=', $dateFrom)
            ->where('date', '<=', $dateTo)
            ->get();

        $this->info("EXPENSES IN DATE RANGE: " . $expenses->count());
        
        $totalExpenses = 0;
        $totalExpensesBase = 0;

        foreach ($expenses as $expense) {
            $this->line("Expense ID: {$expense->id}");
            $this->line("  Original Amount: {$expense->amount}");
            $this->line("  Currency: " . ($expense->currency ? $expense->currency->code : 'NULL'));
            
            // Calculate base currency amount
            $baseAmount = $expense->getAmountInBaseCurrency();
            $this->line("  Base Currency Amount: {$baseAmount}");
            
            $totalExpenses += $expense->amount;
            $totalExpensesBase += $baseAmount;
            $this->line("---");
        }

        $this->info("EXPENSE TOTALS:");
        $this->info("Total Expenses (Original): {$totalExpenses}");
        $this->info("Total Expenses (Base Currency): {$totalExpensesBase}");
        $this->newLine();

        // Check salary releases
        $fromMonth = Carbon::parse($dateFrom)->format('Y-m');
        $toMonth = Carbon::parse($dateTo)->format('Y-m');
        
        if($fromMonth == $toMonth){
            $salaryReleases = SalaryRelease::where('user_id', $userId)
                ->with(['employee', 'currency'])
                ->where('month', $fromMonth)
                ->get();
        } else {
            $salaryReleases = SalaryRelease::where('user_id', $userId)
                ->with(['employee', 'currency'])
                ->where('release_date', '>=', $dateFrom)
                ->where('release_date', '<=', $dateTo)
                ->get();
        }

        $this->info("SALARY RELEASES: " . $salaryReleases->count());
        
        $totalSalariesBase = 0;
        foreach ($salaryReleases as $salary) {
            $baseAmount = $salary->getTotalAmountInBaseCurrency();
            $this->line("Salary ID: {$salary->id}, Base Amount: {$baseAmount}");
            $totalSalariesBase += $baseAmount;
        }
        $this->info("Total Salaries (Base Currency): {$totalSalariesBase}");
        $this->newLine();

        // Check bonuses
        $bonuses = Bonus::where('user_id', $userId)
            ->with(['employee', 'currency'])
            ->where('date', '>=', $dateFrom)
            ->where('date', '<=', $dateTo)
            ->get();

        $this->info("BONUSES: " . $bonuses->count());
        
        $totalBonusesBase = 0;
        foreach ($bonuses as $bonus) {
            $baseAmount = $bonus->getAmountInBaseCurrency();
            $this->line("Bonus ID: {$bonus->id}, Base Amount: {$baseAmount}");
            $totalBonusesBase += $baseAmount;
        }
        $this->info("Total Bonuses (Base Currency): {$totalBonusesBase}");
        $this->newLine();

        // Check payments in range
        $totalPaymentsInRange = $invoices->sum(function($invoice) use ($dateFrom, $dateTo) {
            return $invoice->payments->filter(function($payment) use ($dateFrom, $dateTo) {
                return $payment->payment_date >= $dateFrom && $payment->payment_date <= $dateTo;
            })->sum(function($payment) use ($invoice) {
                return $invoice->convertAmountToBase($payment->amount);
            });
        });

        $this->info("FINAL SUMMARY (Base Currency):");
        $this->info("Payments Received: {$totalPaymentsInRange}");
        $this->info("Total Invoices: {$totalInvoiceAmountBase}");
        $this->info("Total Expenses: {$totalExpensesBase}");
        $this->info("Total Salaries: {$totalSalariesBase}");
        $this->info("Total Bonuses: {$totalBonusesBase}");

        return 0;
    }
}
