<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\Bonus;
use App\Models\SalaryRelease;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ComprehensiveCurrencySeeder extends Seeder
{
    /**
     * Run the database seeder.
     * Creates comprehensive test data with multiple currencies to test conversion logic
     */
    public function run(): void
    {
        $this->command->info('🌍 Starting Comprehensive Currency Seeder...');

        // Create test user
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ]
        );
        $this->command->info("✓ User created: {$user->email}");

        // Create currencies
        $this->command->info('💱 Creating currencies...');
        
        // Base currency: PKR
        $pkr = Currency::updateOrCreate(
            ['user_id' => $user->id, 'code' => 'PKR'],
            [
                'name' => 'Pakistani Rupee',
                'symbol' => 'Rs.',
                'country' => 'Pakistan',
                'conversion_rate' => 1,
                'is_base' => true,
                'is_active' => true,
            ]
        );
        $this->command->info("  ✓ Base Currency: PKR (Rs.)");

        // USD with rate 282
        $usd = Currency::updateOrCreate(
            ['user_id' => $user->id, 'code' => 'USD'],
            [
                'name' => 'US Dollar',
                'symbol' => '$',
                'country' => 'United States',
                'conversion_rate' => 282, // 1 USD = 282 PKR
                'is_base' => false,
                'is_active' => true,
            ]
        );
        $this->command->info("  ✓ USD: $1 = Rs.282");

        // EUR with rate 310
        $eur = Currency::updateOrCreate(
            ['user_id' => $user->id, 'code' => 'EUR'],
            [
                'name' => 'Euro',
                'symbol' => '€',
                'country' => 'European Union',
                'conversion_rate' => 310, // 1 EUR = 310 PKR
                'is_base' => false,
                'is_active' => true,
            ]
        );
        $this->command->info("  ✓ EUR: €1 = Rs.310");

        // GBP with rate 360
        $gbp = Currency::updateOrCreate(
            ['user_id' => $user->id, 'code' => 'GBP'],
            [
                'name' => 'British Pound',
                'symbol' => '£',
                'country' => 'United Kingdom',
                'conversion_rate' => 360, // 1 GBP = 360 PKR
                'is_base' => false,
                'is_active' => true,
            ]
        );
        $this->command->info("  ✓ GBP: £1 = Rs.360");

        // Create clients
        $this->command->info('👥 Creating clients...');
        
        $client1 = Client::updateOrCreate(
            ['user_id' => $user->id, 'email' => 'contact@abc.com'],
            [
                'name' => 'ABC Corporation',
                'primary_contact' => '+1-555-0101',
                'website' => 'https://abc.com',
            ]
        );

        $client2 = Client::updateOrCreate(
            ['user_id' => $user->id, 'email' => 'info@xyz.com'],
            [
                'name' => 'XYZ Industries',
                'primary_contact' => '+1-555-0102',
                'website' => 'https://xyz.com',
            ]
        );

        $client3 = Client::updateOrCreate(
            ['user_id' => $user->id, 'email' => 'hello@techcorp.com'],
            [
                'name' => 'TechCorp Solutions',
                'primary_contact' => '+44-20-1234-5678',
                'website' => 'https://techcorp.com',
            ]
        );

        $this->command->info("  ✓ Created 3 clients");

        // Create employees with different currencies
        $this->command->info('👨‍💼 Creating employees...');
        
        $employee1 = Employee::updateOrCreate(
            ['user_id' => $user->id, 'email' => 'john@example.com'],
            [
                'name' => 'John Doe',
                'primary_contact' => '+92-300-1234567',
                'role' => 'Sales Manager',
                'employment_type' => 'Onsite',
                'salary' => 50000, // PKR
                'commission_rate' => 5.00,
                'joining_date' => Carbon::now()->subMonths(12),
                'currency_id' => $pkr->id,
                'geolocation_required' => true,
            ]
        );

        $employee2 = Employee::updateOrCreate(
            ['user_id' => $user->id, 'email' => 'jane@example.com'],
            [
                'name' => 'Jane Smith',
                'primary_contact' => '+92-300-7654321',
                'role' => 'Senior Developer',
                'employment_type' => 'Remote',
                'salary' => 45000, // PKR
                'commission_rate' => 3.00,
                'joining_date' => Carbon::now()->subMonths(8),
                'currency_id' => $pkr->id,
                'geolocation_required' => false,
            ]
        );

        $employee3 = Employee::updateOrCreate(
            ['user_id' => $user->id, 'email' => 'mike@example.com'],
            [
                'name' => 'Mike Johnson',
                'primary_contact' => '+92-300-9876543',
                'role' => 'Business Developer',
                'employment_type' => 'Remote',
                'salary' => 40000, // PKR
                'commission_rate' => 7.00,
                'joining_date' => Carbon::now()->subMonths(6),
                'currency_id' => $pkr->id,
                'geolocation_required' => false,
            ]
        );

        $this->command->info("  ✓ Created 3 employees with commission rates");

        // Create invoices with different currencies and payments
        $this->command->info('📄 Creating invoices with multi-currency payments...');

        // Invoice 1: USD invoice with payments in October and November
        $invoice1 = Invoice::updateOrCreate(
            ['user_id' => $user->id, 'client_id' => $client1->id, 'amount' => 1000],
            [
                'employee_id' => $employee1->id,
                'currency_id' => $usd->id,
                'status' => 'Partial Paid',
                'amount' => 1000, // $1,000
                'tax' => 50, // $50
                'paid_amount' => 250,
                'remaining_amount' => 750,
                'due_date' => Carbon::now()->addDays(30),
                'invoice_date' => Carbon::now()->subMonths(2),
                'approval_status' => 'approved',
                'created_at' => Carbon::now()->subMonths(2),
                'exchange_rate_at_time' => $usd->conversion_rate,
            ]
        );

        // Payment in October (for November salary)
        Payment::updateOrCreate(
            [
                'invoice_id' => $invoice1->id,
                'payment_date' => Carbon::now()->subMonth()->setDay(15),
            ],
            [
                'user_id' => $user->id,
                'amount' => 250, // $250
                'payment_month' => Carbon::now()->subMonth()->format('Y-m'),
                'commission_paid' => false,
                'notes' => 'First payment - October',
            ]
        );

        $this->command->info("  ✓ Invoice 1: $1,000 USD (ABC Corp) - $250 paid in October");

        // Invoice 2: PKR invoice fully paid in October
        $invoice2 = Invoice::updateOrCreate(
            ['user_id' => $user->id, 'client_id' => $client2->id, 'amount' => 110000],
            [
                'employee_id' => $employee2->id,
                'currency_id' => $pkr->id,
                'status' => 'Payment Done',
                'amount' => 110000, // Rs.110,000
                'tax' => 5500, // Rs.5,500
                'paid_amount' => 110000,
                'remaining_amount' => 0,
                'due_date' => Carbon::now()->addDays(15),
                'invoice_date' => Carbon::now()->subMonths(2),
                'approval_status' => 'approved',
                'created_at' => Carbon::now()->subMonths(2),
                'exchange_rate_at_time' => $pkr->conversion_rate,
            ]
        );

        Payment::updateOrCreate(
            [
                'invoice_id' => $invoice2->id,
                'payment_date' => Carbon::now()->subMonth()->setDay(20),
            ],
            [
                'user_id' => $user->id,
                'amount' => 110000, // Rs.110,000
                'payment_month' => Carbon::now()->subMonth()->format('Y-m'),
                'commission_paid' => false,
                'notes' => 'Full payment - October',
            ]
        );

        $this->command->info("  ✓ Invoice 2: Rs.110,000 PKR (XYZ Industries) - Fully paid in October");

        // Invoice 3: EUR invoice with payment in October
        $invoice3 = Invoice::updateOrCreate(
            ['user_id' => $user->id, 'client_id' => $client3->id, 'amount' => 500],
            [
                'employee_id' => $employee3->id,
                'currency_id' => $eur->id,
                'status' => 'Payment Done',
                'amount' => 500, // €500
                'tax' => 25, // €25
                'paid_amount' => 500,
                'remaining_amount' => 0,
                'due_date' => Carbon::now()->addDays(20),
                'invoice_date' => Carbon::now()->subMonths(1)->subDays(10),
                'approval_status' => 'approved',
                'created_at' => Carbon::now()->subMonths(1)->subDays(10),
                'exchange_rate_at_time' => $eur->conversion_rate,
            ]
        );

        Payment::updateOrCreate(
            [
                'invoice_id' => $invoice3->id,
                'payment_date' => Carbon::now()->subMonth()->setDay(25),
            ],
            [
                'user_id' => $user->id,
                'amount' => 500, // €500
                'payment_month' => Carbon::now()->subMonth()->format('Y-m'),
                'commission_paid' => false,
                'notes' => 'Full payment - October',
            ]
        );

        $this->command->info("  ✓ Invoice 3: €500 EUR (TechCorp) - Fully paid in October");

        // Invoice 4: GBP invoice with payment in current month (should NOT be in November salary)
        $invoice4 = Invoice::updateOrCreate(
            ['user_id' => $user->id, 'client_id' => $client1->id, 'amount' => 300],
            [
                'employee_id' => $employee1->id,
                'currency_id' => $gbp->id,
                'status' => 'Payment Done',
                'amount' => 300, // £300
                'tax' => 15, // £15
                'paid_amount' => 300,
                'remaining_amount' => 0,
                'due_date' => Carbon::now()->addDays(25),
                'invoice_date' => Carbon::now()->subDays(20),
                'approval_status' => 'approved',
                'created_at' => Carbon::now()->subDays(20),
                'exchange_rate_at_time' => $gbp->conversion_rate,
            ]
        );

        Payment::updateOrCreate(
            [
                'invoice_id' => $invoice4->id,
                'payment_date' => Carbon::now()->setDay(5),
            ],
            [
                'user_id' => $user->id,
                'amount' => 300, // £300
                'payment_month' => Carbon::now()->format('Y-m'),
                'commission_paid' => false,
                'notes' => 'Payment in current month',
            ]
        );

        $this->command->info("  ✓ Invoice 4: £300 GBP (ABC Corp) - Paid in current month");

        // Invoice 5: PKR invoice pending
        $invoice5 = Invoice::updateOrCreate(
            ['user_id' => $user->id, 'client_id' => $client2->id, 'amount' => 75000],
            [
                'employee_id' => $employee2->id,
                'currency_id' => $pkr->id,
                'status' => 'Pending',
                'amount' => 75000, // Rs.75,000
                'tax' => 3750, // Rs.3,750
                'paid_amount' => 0,
                'remaining_amount' => 75000,
                'due_date' => Carbon::now()->addDays(45),
                'invoice_date' => Carbon::now()->subDays(5),
                'approval_status' => 'approved',
                'created_at' => Carbon::now()->subDays(5),
                'exchange_rate_at_time' => $pkr->conversion_rate,
            ]
        );

        $this->command->info("  ✓ Invoice 5: Rs.75,000 PKR (XYZ Industries) - Pending");

        // Create expenses in different currencies
        $this->command->info('💰 Creating expenses...');

        Expense::updateOrCreate(
            [
                'user_id' => $user->id,
                'description' => 'Office Rent',
                'date' => Carbon::now()->subMonth()->setDay(1),
            ],
            [
                'amount' => 50000, // Rs.50,000
                'currency_id' => $pkr->id,
                'exchange_rate_at_time' => $pkr->conversion_rate,
            ]
        );

        Expense::updateOrCreate(
            [
                'user_id' => $user->id,
                'description' => 'Software Licenses (Adobe)',
                'date' => Carbon::now()->subMonth()->setDay(10),
            ],
            [
                'amount' => 100, // $100
                'currency_id' => $usd->id,
                'exchange_rate_at_time' => $usd->conversion_rate,
            ]
        );

        Expense::updateOrCreate(
            [
                'user_id' => $user->id,
                'description' => 'Marketing Campaign',
                'date' => Carbon::now()->subMonth()->setDay(15),
            ],
            [
                'amount' => 200, // €200
                'currency_id' => $eur->id,
                'exchange_rate_at_time' => $eur->conversion_rate,
            ]
        );

        Expense::updateOrCreate(
            [
                'user_id' => $user->id,
                'description' => 'Internet & Utilities',
                'date' => Carbon::now()->setDay(5),
            ],
            [
                'amount' => 15000, // Rs.15,000
                'currency_id' => $pkr->id,
                'exchange_rate_at_time' => $pkr->conversion_rate,
            ]
        );

        $this->command->info("  ✓ Created 4 expenses in multiple currencies");

        // Create bonuses
        $this->command->info('🎁 Creating bonuses...');

        Bonus::updateOrCreate(
            [
                'user_id' => $user->id,
                'employee_id' => $employee1->id,
                'date' => Carbon::now()->subMonth()->setDay(25),
            ],
            [
                'amount' => 10000, // Rs.10,000
                'description' => 'Performance Bonus - Q3',
                'release_type' => 'with_salary',
                'released' => false,
                'currency_id' => $pkr->id,
                'exchange_rate_at_time' => $pkr->conversion_rate,
            ]
        );

        Bonus::updateOrCreate(
            [
                'user_id' => $user->id,
                'employee_id' => $employee2->id,
                'date' => Carbon::now()->subMonth()->setDay(28),
            ],
            [
                'amount' => 50, // $50
                'description' => 'Project Completion Bonus',
                'release_type' => 'with_salary',
                'released' => false,
                'currency_id' => $usd->id,
                'exchange_rate_at_time' => $usd->conversion_rate,
            ]
        );

        $this->command->info("  ✓ Created 2 unreleased bonuses");

        // Summary
        $this->command->info('');
        $this->command->info('📊 SEEDING SUMMARY:');
        $this->command->info('==================');
        $this->command->info('Currencies: 4 (PKR base, USD, EUR, GBP)');
        $this->command->info('Clients: 3');
        $this->command->info('Employees: 3 (all with commission rates)');
        $this->command->info('Invoices: 5 (multi-currency)');
        $this->command->info('Payments: 5 (across different months)');
        $this->command->info('Expenses: 4 (multi-currency)');
        $this->command->info('Bonuses: 2 (unreleased)');
        $this->command->info('');
        $this->command->info('💡 TEST SCENARIOS:');
        $this->command->info('1. Invoice totals should convert to PKR correctly');
        $this->command->info('2. October payments: $250 + Rs.110,000 + €500 = Rs.340,500 in base currency');
        $this->command->info('3. Commission for November salary should only count October payments');
        $this->command->info('4. Current month payment (£300) should NOT be in November salary');
        $this->command->info('5. Expenses total in base currency: Rs.50,000 + $100 + €200 = Rs.140,200');
        $this->command->info('');
        $this->command->info('✅ Comprehensive Currency Seeder completed successfully!');
    }
}
