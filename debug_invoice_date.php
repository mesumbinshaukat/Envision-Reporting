<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\Invoice;
use App\Models\User;

// Bootstrap Laravel
$app = new Application(realpath(__DIR__));
$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);
$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);
$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DEBUGGING INVOICE_DATE FUNCTIONALITY ===\n\n";

try {
    // Check if invoice_date column exists
    echo "1. Checking if invoice_date column exists...\n";
    $hasColumn = Schema::hasColumn('invoices', 'invoice_date');
    echo "invoice_date column exists: " . ($hasColumn ? 'YES' : 'NO') . "\n\n";
    
    if (!$hasColumn) {
        echo "ERROR: invoice_date column does not exist! Migration may not have been applied.\n";
        echo "Run: php artisan migrate\n\n";
        exit(1);
    }
    
    // Get all invoices and check their invoice_date values
    echo "2. Checking existing invoices...\n";
    $invoices = Invoice::all();
    echo "Total invoices in database: " . $invoices->count() . "\n\n";
    
    if ($invoices->count() > 0) {
        echo "Invoice details:\n";
        foreach ($invoices as $invoice) {
            echo "ID: {$invoice->id}\n";
            echo "Amount: {$invoice->amount}\n";
            echo "Status: {$invoice->status}\n";
            echo "Created At: {$invoice->created_at}\n";
            echo "Invoice Date: " . ($invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : 'NULL') . "\n";
            echo "User ID: {$invoice->user_id}\n";
            echo "---\n";
        }
    }
    
    // Test the specific date range query that should work
    echo "3. Testing February date range query (2024-02-01 to 2024-02-27)...\n";
    $febInvoices = Invoice::where('invoice_date', '>=', '2024-02-01')
        ->where('invoice_date', '<=', '2024-02-27')
        ->get();
    
    echo "Invoices found in February 2024 range: " . $febInvoices->count() . "\n";
    
    if ($febInvoices->count() > 0) {
        echo "February invoices:\n";
        foreach ($febInvoices as $invoice) {
            echo "ID: {$invoice->id}, Amount: {$invoice->amount}, Invoice Date: {$invoice->invoice_date->format('Y-m-d')}\n";
        }
    }
    
    // Test with current year (2025)
    echo "\n4. Testing February 2025 date range query (2025-02-01 to 2025-02-27)...\n";
    $feb2025Invoices = Invoice::where('invoice_date', '>=', '2025-02-01')
        ->where('invoice_date', '<=', '2025-02-27')
        ->get();
    
    echo "Invoices found in February 2025 range: " . $feb2025Invoices->count() . "\n";
    
    if ($feb2025Invoices->count() > 0) {
        echo "February 2025 invoices:\n";
        foreach ($feb2025Invoices as $invoice) {
            echo "ID: {$invoice->id}, Amount: {$invoice->amount}, Invoice Date: {$invoice->invoice_date->format('Y-m-d')}\n";
        }
    }
    
    // Check if there are any invoices with NULL invoice_date
    echo "\n5. Checking for invoices with NULL invoice_date...\n";
    $nullDateInvoices = Invoice::whereNull('invoice_date')->get();
    echo "Invoices with NULL invoice_date: " . $nullDateInvoices->count() . "\n";
    
    if ($nullDateInvoices->count() > 0) {
        echo "These invoices need their invoice_date set:\n";
        foreach ($nullDateInvoices as $invoice) {
            echo "ID: {$invoice->id}, Amount: {$invoice->amount}, Created: {$invoice->created_at}\n";
        }
        
        // Fix NULL invoice_dates by setting them to created_at date
        echo "\nFixing NULL invoice_dates...\n";
        foreach ($nullDateInvoices as $invoice) {
            $invoice->invoice_date = $invoice->created_at->format('Y-m-d');
            $invoice->save();
            echo "Fixed invoice ID {$invoice->id} - set invoice_date to {$invoice->invoice_date}\n";
        }
    }
    
    // Test the ReportController query logic
    echo "\n6. Testing ReportController query logic...\n";
    $userId = 1; // Assuming user ID 1, adjust as needed
    
    $reportInvoices = Invoice::where('user_id', $userId)
        ->with(['client', 'employee', 'payments', 'currency'])
        ->where('invoice_date', '>=', '2025-02-01')
        ->where('invoice_date', '<=', '2025-02-27')
        ->get();
    
    echo "Report query results for user {$userId} (Feb 2025): " . $reportInvoices->count() . "\n";
    
    if ($reportInvoices->count() > 0) {
        foreach ($reportInvoices as $invoice) {
            echo "ID: {$invoice->id}, Amount: {$invoice->amount}, Invoice Date: {$invoice->invoice_date->format('Y-m-d')}, User: {$invoice->user_id}\n";
        }
    }
    
    echo "\n=== DEBUGGING COMPLETE ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
