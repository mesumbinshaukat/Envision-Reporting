<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class InvoiceDateReportingTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $client;
    private $employee;
    private $currency;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
        ]);
        
        // Create test client
        $this->client = Client::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Client',
        ]);
        
        // Create test employee
        $this->employee = Employee::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Employee',
            'commission_rate' => 10,
        ]);
        
        // Create base currency
        $this->currency = Currency::factory()->create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'is_base' => true,
            'conversion_rate' => 1.0,
        ]);
    }

    public function test_invoice_date_field_exists_in_database()
    {
        // Test that invoice_date column exists
        $invoice = Invoice::create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'employee_id' => $this->employee->id,
            'currency_id' => $this->currency->id,
            'status' => 'Pending',
            'invoice_date' => '2024-02-05',
            'amount' => 10000,
            'tax' => 0,
            'paid_amount' => 0,
            'remaining_amount' => 10000,
        ]);
        
        $this->assertNotNull($invoice->invoice_date);
        $this->assertEquals('2024-02-05', $invoice->invoice_date->format('Y-m-d'));
    }

    public function test_report_filtering_by_invoice_date()
    {
        // Create invoices with different invoice_dates but same created_at (today)
        $feb5Invoice = Invoice::create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'employee_id' => $this->employee->id,
            'currency_id' => $this->currency->id,
            'status' => 'Pending',
            'invoice_date' => '2024-02-05',
            'amount' => 10000,
            'tax' => 0,
            'paid_amount' => 0,
            'remaining_amount' => 10000,
        ]);
        
        $mar5Invoice = Invoice::create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'employee_id' => $this->employee->id,
            'currency_id' => $this->currency->id,
            'status' => 'Pending',
            'invoice_date' => '2024-03-05',
            'amount' => 15000,
            'tax' => 0,
            'paid_amount' => 0,
            'remaining_amount' => 15000,
        ]);
        
        // Test February date range (should include Feb 5 invoice only)
        $this->actingAs($this->user);
        
        $response = $this->get('/reports?' . http_build_query([
            'date_from' => '2024-02-01',
            'date_to' => '2024-02-27',
        ]));
        
        $response->assertStatus(200);
        
        // Check if the response contains the February invoice
        $response->assertSee('10,000.00'); // Feb invoice amount
        $response->assertDontSee('15,000.00'); // Mar invoice amount should not be visible
    }

    public function test_invoice_controller_filtering_by_invoice_date()
    {
        // Create test invoices
        $feb5Invoice = Invoice::create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'employee_id' => $this->employee->id,
            'currency_id' => $this->currency->id,
            'status' => 'Pending',
            'invoice_date' => '2024-02-05',
            'amount' => 10000,
            'tax' => 0,
            'paid_amount' => 0,
            'remaining_amount' => 10000,
        ]);
        
        $mar5Invoice = Invoice::create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'employee_id' => $this->employee->id,
            'currency_id' => $this->currency->id,
            'status' => 'Pending',
            'invoice_date' => '2024-03-05',
            'amount' => 15000,
            'tax' => 0,
            'paid_amount' => 0,
            'remaining_amount' => 15000,
        ]);
        
        $this->actingAs($this->user);
        
        // Test invoice listing with February date filter
        $response = $this->get('/invoices?' . http_build_query([
            'date_from' => '2024-02-01',
            'date_to' => '2024-02-27',
        ]));
        
        $response->assertStatus(200);
        
        // Should see February invoice but not March invoice
        $response->assertSee('10,000.00');
        $response->assertDontSee('15,000.00');
    }

    public function test_database_query_directly()
    {
        // Create test invoice
        $invoice = Invoice::create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'employee_id' => $this->employee->id,
            'currency_id' => $this->currency->id,
            'status' => 'Pending',
            'invoice_date' => '2024-02-05',
            'amount' => 10000,
            'tax' => 0,
            'paid_amount' => 0,
            'remaining_amount' => 10000,
        ]);
        
        // Test direct database query
        $invoices = Invoice::where('user_id', $this->user->id)
            ->where('invoice_date', '>=', '2024-02-01')
            ->where('invoice_date', '<=', '2024-02-27')
            ->get();
        
        $this->assertCount(1, $invoices);
        $this->assertEquals(10000, $invoices->first()->amount);
        $this->assertEquals('2024-02-05', $invoices->first()->invoice_date->format('Y-m-d'));
    }

    public function test_api_filtering_by_invoice_date()
    {
        // Create test invoice
        $invoice = Invoice::create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'employee_id' => $this->employee->id,
            'currency_id' => $this->currency->id,
            'status' => 'Pending',
            'invoice_date' => '2024-02-05',
            'amount' => 10000,
            'tax' => 0,
            'paid_amount' => 0,
            'remaining_amount' => 10000,
        ]);
        
        $this->actingAs($this->user);
        
        // Test API endpoint with invoice_date filtering
        $response = $this->getJson('/api/v1/invoices?' . http_build_query([
            'invoice_date_from' => '2024-02-01',
            'invoice_date_to' => '2024-02-27',
        ]));
        
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.amount', 10000);
    }
}
