<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\User;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 100, 5000);
        $tax = $amount * 0.05;
        
        return [
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            'employee_id' => Employee::factory(),
            'currency_id' => Currency::factory()->create(['is_base' => true]),
            'amount' => $amount,
            'tax' => $tax,
            'paid_amount' => 0,
            'remaining_amount' => $amount,
            'status' => 'Pending',
            'approval_status' => 'approved',
            'invoice_date' => Carbon::now(),
            'due_date' => Carbon::now()->addDays(30),
            'exchange_rate_at_time' => 1.000000,
        ];
    }
}
