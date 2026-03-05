<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TestInvoiceDateReporting extends Command
{
    protected $signature = 'test:invoice-date-reporting';
    protected $description = 'Test invoice_date reporting functionality with correct year';

    public function handle()
    {
        $this->info('=== TESTING INVOICE_DATE REPORTING FUNCTIONALITY ===');
        $this->newLine();

        // Test February 2026 date range query (correct year)
        $this->info('1. Testing February 2026 date range query (2026-02-01 to 2026-02-27)...');
        $feb2026Invoices = Invoice::where('invoice_date', '>=', '2026-02-01')
            ->where('invoice_date', '<=', '2026-02-27')
            ->get();

        $this->info('Invoices found in February 2026 range: ' . $feb2026Invoices->count());

        if ($feb2026Invoices->count() > 0) {
            $this->info('February 2026 invoices:');
            foreach ($feb2026Invoices as $invoice) {
                $this->line("ID: {$invoice->id}, Amount: {$invoice->amount}, Invoice Date: {$invoice->invoice_date->format('Y-m-d')}, User: {$invoice->user_id}");
            }
        }

        // Test the ReportController query logic for user 1 with February 2026
        $this->info('2. Testing ReportController query logic for user 1 (Feb 2026)...');
        $userId = 1;
        
        $reportInvoices = Invoice::where('user_id', $userId)
            ->with(['client', 'employee', 'payments', 'currency'])
            ->where('invoice_date', '>=', '2026-02-01')
            ->where('invoice_date', '<=', '2026-02-27')
            ->get();

        $this->info("Report query results for user {$userId} (Feb 2026): " . $reportInvoices->count());
        
        if ($reportInvoices->count() > 0) {
            $totalAmount = 0;
            foreach ($reportInvoices as $invoice) {
                $this->line("  ID: {$invoice->id}, Amount: {$invoice->amount}, Invoice Date: {$invoice->invoice_date->format('Y-m-d')}");
                $totalAmount += $invoice->amount;
            }
            $this->info("Total amount: {$totalAmount}");
        }

        // Test with exact date 2026-02-05
        $this->info('3. Testing with exact date 2026-02-05...');
        $exactDateInvoices = Invoice::where('invoice_date', '2026-02-05')->get();
        $this->info('Invoices with exact date 2026-02-05: ' . $exactDateInvoices->count());
        
        if ($exactDateInvoices->count() > 0) {
            foreach ($exactDateInvoices as $invoice) {
                $this->line("ID: {$invoice->id}, Amount: {$invoice->amount}, User: {$invoice->user_id}");
            }
        }

        // Test the actual SQL query that would be used in reports
        $this->info('4. Testing raw SQL query (simulating ReportController)...');
        $results = DB::select("
            SELECT id, amount, invoice_date, user_id, status
            FROM invoices 
            WHERE user_id = ? 
            AND invoice_date >= ? 
            AND invoice_date <= ?
            AND deleted_at IS NULL
        ", [1, '2026-02-01', '2026-02-27']);

        $this->info('Raw SQL results: ' . count($results));
        foreach ($results as $result) {
            $this->line("ID: {$result->id}, Amount: {$result->amount}, Invoice Date: {$result->invoice_date}");
        }

        $this->newLine();
        $this->info('=== TESTING COMPLETE ===');
        $this->newLine();
        
        // Provide instructions for testing the web interface
        $this->info('TO TEST THE WEB INTERFACE:');
        $this->info('1. Go to: http://127.0.0.1:8000/reports');
        $this->info('2. Set date range: From 2026-02-01 To 2026-02-27');
        $this->info('3. You should see the Rs.10,000 invoice (ID: 6) in the results');
        $this->newLine();
        
        return 0;
    }
}
