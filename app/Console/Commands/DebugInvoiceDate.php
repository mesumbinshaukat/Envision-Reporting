<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DebugInvoiceDate extends Command
{
    protected $signature = 'debug:invoice-date';
    protected $description = 'Debug invoice_date functionality';

    public function handle()
    {
        $this->info('=== DEBUGGING INVOICE_DATE FUNCTIONALITY ===');
        $this->newLine();

        // Check if invoice_date column exists
        $this->info('1. Checking if invoice_date column exists...');
        $hasColumn = Schema::hasColumn('invoices', 'invoice_date');
        $this->info('invoice_date column exists: ' . ($hasColumn ? 'YES' : 'NO'));
        $this->newLine();

        if (!$hasColumn) {
            $this->error('ERROR: invoice_date column does not exist! Migration may not have been applied.');
            $this->error('Run: php artisan migrate');
            return 1;
        }

        // Get all invoices and check their invoice_date values
        $this->info('2. Checking existing invoices...');
        $invoices = Invoice::all();
        $this->info('Total invoices in database: ' . $invoices->count());
        $this->newLine();

        if ($invoices->count() > 0) {
            $this->info('Invoice details:');
            foreach ($invoices as $invoice) {
                $this->line("ID: {$invoice->id}");
                $this->line("Amount: {$invoice->amount}");
                $this->line("Status: {$invoice->status}");
                $this->line("Created At: {$invoice->created_at}");
                $this->line("Invoice Date: " . ($invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : 'NULL'));
                $this->line("User ID: {$invoice->user_id}");
                $this->line("---");
            }
        }

        // Check if there are any invoices with NULL invoice_date
        $this->info('3. Checking for invoices with NULL invoice_date...');
        $nullDateInvoices = Invoice::whereNull('invoice_date')->get();
        $this->info('Invoices with NULL invoice_date: ' . $nullDateInvoices->count());

        if ($nullDateInvoices->count() > 0) {
            $this->info('These invoices need their invoice_date set:');
            foreach ($nullDateInvoices as $invoice) {
                $this->line("ID: {$invoice->id}, Amount: {$invoice->amount}, Created: {$invoice->created_at}");
            }

            if ($this->confirm('Fix NULL invoice_dates by setting them to created_at date?')) {
                foreach ($nullDateInvoices as $invoice) {
                    $invoice->invoice_date = $invoice->created_at->format('Y-m-d');
                    $invoice->save();
                    $this->line("Fixed invoice ID {$invoice->id} - set invoice_date to {$invoice->invoice_date}");
                }
                $this->info('All NULL invoice_dates have been fixed.');
            }
        }

        // Test February 2025 date range query
        $this->info('4. Testing February 2025 date range query (2025-02-01 to 2025-02-27)...');
        $feb2025Invoices = Invoice::where('invoice_date', '>=', '2025-02-01')
            ->where('invoice_date', '<=', '2025-02-27')
            ->get();

        $this->info('Invoices found in February 2025 range: ' . $feb2025Invoices->count());

        if ($feb2025Invoices->count() > 0) {
            $this->info('February 2025 invoices:');
            foreach ($feb2025Invoices as $invoice) {
                $this->line("ID: {$invoice->id}, Amount: {$invoice->amount}, Invoice Date: {$invoice->invoice_date->format('Y-m-d')}, User: {$invoice->user_id}");
            }
        }

        // Test the ReportController query logic for each user
        $this->info('5. Testing ReportController query logic for all users...');
        $users = User::all();
        
        foreach ($users as $user) {
            $reportInvoices = Invoice::where('user_id', $user->id)
                ->with(['client', 'employee', 'payments', 'currency'])
                ->where('invoice_date', '>=', '2025-02-01')
                ->where('invoice_date', '<=', '2025-02-27')
                ->get();

            if ($reportInvoices->count() > 0) {
                $this->info("Report query results for user {$user->id} ({$user->name}) - Feb 2025: " . $reportInvoices->count());
                foreach ($reportInvoices as $invoice) {
                    $this->line("  ID: {$invoice->id}, Amount: {$invoice->amount}, Invoice Date: {$invoice->invoice_date->format('Y-m-d')}");
                }
            }
        }

        // Test with exact date
        $this->info('6. Testing with exact date 2025-02-05...');
        $exactDateInvoices = Invoice::where('invoice_date', '2025-02-05')->get();
        $this->info('Invoices with exact date 2025-02-05: ' . $exactDateInvoices->count());
        
        if ($exactDateInvoices->count() > 0) {
            foreach ($exactDateInvoices as $invoice) {
                $this->line("ID: {$invoice->id}, Amount: {$invoice->amount}, User: {$invoice->user_id}");
            }
        }

        $this->newLine();
        $this->info('=== DEBUGGING COMPLETE ===');
        
        return 0;
    }
}
