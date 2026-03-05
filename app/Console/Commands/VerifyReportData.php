<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\Expense;
use App\Models\SalaryRelease;
use App\Models\Bonus;
use App\Models\User;
use Carbon\Carbon;

class VerifyReportData extends Command
{
    protected $signature = 'verify:report-data {--date_from=2026-02-01} {--date_to=2026-02-27} {--user_id=1}';
    protected $description = 'Verify report data matches what should be displayed';

    public function handle()
    {
        $dateFrom = $this->option('date_from');
        $dateTo = $this->option('date_to');
        $userId = $this->option('user_id');

        $this->info("=== VERIFYING REPORT DATA FOR USER {$userId} ===");
        $this->info("Date Range: {$dateFrom} to {$dateTo}");
        $this->newLine();

        // Get invoices by invoice_date in the selected date range
        $invoices = Invoice::where('user_id', $userId)
            ->with(['client', 'employee', 'payments', 'currency'])
            ->where('invoice_date', '>=', $dateFrom)
            ->where('invoice_date', '<=', $dateTo)
            ->get();

        $this->info("1. INVOICES ANALYSIS:");
        $this->info("Total invoices found: " . $invoices->count());
        
        $totalInvoiceAmount = 0;
        $totalPaymentsInRange = 0;
        $totalProcessingFees = 0;

        foreach ($invoices as $invoice) {
            $this->line("Invoice ID: {$invoice->id}");
            $this->line("  Amount: Rs.{$invoice->amount}");
            $this->line("  Status: {$invoice->status}");
            $this->line("  Invoice Date: {$invoice->invoice_date->format('Y-m-d')}");
            $this->line("  Client: " . ($invoice->client ? $invoice->client->name : ($invoice->one_time_client_name ?? 'One-Time')));
            $this->line("  Employee: " . ($invoice->employee ? $invoice->employee->name : 'Self'));
            $this->line("  Processing Fee: Rs." . ($invoice->payment_processing_fee ?? 0));
            
            $totalInvoiceAmount += $invoice->amount;
            $totalProcessingFees += ($invoice->payment_processing_fee ?? 0);
            
            // Check payments within date range
            $paymentsInRange = $invoice->payments->filter(function($payment) use ($dateFrom, $dateTo) {
                return $payment->payment_date >= $dateFrom && $payment->payment_date <= $dateTo;
            });
            
            $paymentAmount = $paymentsInRange->sum('amount');
            $totalPaymentsInRange += $paymentAmount;
            
            $this->line("  Payments in range: Rs.{$paymentAmount}");
            $this->line("---");
        }

        $this->info("INVOICE TOTALS:");
        $this->info("Total Invoice Amount: Rs.{$totalInvoiceAmount}");
        $this->info("Total Payments in Range: Rs.{$totalPaymentsInRange}");
        $this->info("Total Processing Fees: Rs.{$totalProcessingFees}");
        $this->newLine();

        // Get expenses in date range
        $expenses = Expense::where('user_id', $userId)
            ->with('currency')
            ->where('date', '>=', $dateFrom)
            ->where('date', '<=', $dateTo)
            ->get();

        $this->info("2. EXPENSES ANALYSIS:");
        $this->info("Total expenses found: " . $expenses->count());
        
        $totalExpenses = 0;
        foreach ($expenses as $expense) {
            $this->line("Expense ID: {$expense->id}");
            $this->line("  Amount: Rs.{$expense->amount}");
            $this->line("  Date: {$expense->date}");
            $this->line("  Description: {$expense->description}");
            $totalExpenses += $expense->amount;
            $this->line("---");
        }
        $this->info("Total Expenses: Rs.{$totalExpenses}");
        $this->newLine();

        // Get salary releases
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

        $this->info("3. SALARY RELEASES ANALYSIS:");
        $this->info("Total salary releases found: " . $salaryReleases->count());
        
        $totalSalaries = 0;
        foreach ($salaryReleases as $salary) {
            $this->line("Salary ID: {$salary->id}");
            $this->line("  Total Amount: Rs.{$salary->total_amount}");
            $this->line("  Month: {$salary->month}");
            $this->line("  Employee: " . ($salary->employee ? $salary->employee->name : 'N/A'));
            $totalSalaries += $salary->total_amount;
            $this->line("---");
        }
        $this->info("Total Salaries: Rs.{$totalSalaries}");
        $this->newLine();

        // Get bonuses
        $bonuses = Bonus::where('user_id', $userId)
            ->with(['employee', 'currency'])
            ->where('date', '>=', $dateFrom)
            ->where('date', '<=', $dateTo)
            ->get();

        $this->info("4. BONUSES ANALYSIS:");
        $this->info("Total bonuses found: " . $bonuses->count());
        
        $totalBonuses = 0;
        foreach ($bonuses as $bonus) {
            $this->line("Bonus ID: {$bonus->id}");
            $this->line("  Amount: Rs.{$bonus->amount}");
            $this->line("  Date: {$bonus->date}");
            $this->line("  Employee: " . ($bonus->employee ? $bonus->employee->name : 'N/A'));
            $totalBonuses += $bonus->amount;
            $this->line("---");
        }
        $this->info("Total Bonuses: Rs.{$totalBonuses}");
        $this->newLine();

        // Calculate net income
        $netIncome = $totalPaymentsInRange - $totalExpenses - $totalSalaries - $totalProcessingFees;

        $this->info("5. SUMMARY (should match report):");
        $this->info("Payments Received: Rs.{$totalPaymentsInRange}");
        $this->info("Total Invoices: Rs.{$totalInvoiceAmount}");
        $this->info("Processing Fees: Rs.{$totalProcessingFees}");
        $this->info("Total Expenses: Rs.{$totalExpenses}");
        $this->info("Total Salaries: Rs.{$totalSalaries}");
        $this->info("Total Bonuses: Rs.{$totalBonuses}");
        $this->info("Net Income: Rs.{$netIncome}");

        return 0;
    }
}
