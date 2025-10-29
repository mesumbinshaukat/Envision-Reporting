<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create sample clients
        $client1 = $user->clients()->create([
            'name' => 'ABC Corporation',
            'email' => 'contact@abc.com',
            'primary_contact' => '+1-555-0101',
            'website' => 'https://abc.com',
        ]);

        $client2 = $user->clients()->create([
            'name' => 'XYZ Industries',
            'email' => 'info@xyz.com',
            'primary_contact' => '+1-555-0102',
            'website' => 'https://xyz.com',
        ]);

        // Create sample employees
        $employee1 = $user->employees()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'primary_contact' => '+1-555-0201',
            'role' => 'Sales Manager',
            'employment_type' => 'Onsite',
            'salary' => 5000,
            'commission_rate' => 5.00,
            'joining_date' => now()->subMonths(6),
        ]);

        $employee2 = $user->employees()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'primary_contact' => '+1-555-0202',
            'role' => 'Developer',
            'employment_type' => 'Remote',
            'salary' => 4500,
            'commission_rate' => 0,
            'joining_date' => now()->subMonths(3),
        ]);

        // Create sample invoices
        $user->invoices()->create([
            'client_id' => $client1->id,
            'employee_id' => $employee1->id,
            'status' => 'Payment Done',
            'amount' => 10000,
            'tax' => 1000,
            'due_date' => now()->addDays(30),
        ]);

        $user->invoices()->create([
            'client_id' => $client2->id,
            'employee_id' => null,
            'status' => 'Pending',
            'amount' => 7500,
            'tax' => 750,
            'due_date' => now()->addDays(15),
        ]);

        // Create sample expenses
        $user->expenses()->create([
            'description' => 'Office Supplies',
            'amount' => 500,
            'date' => now()->subDays(5),
        ]);

        $user->expenses()->create([
            'description' => 'Software Licenses',
            'amount' => 1200,
            'date' => now()->subDays(10),
        ]);

        // Create sample bonuses
        $user->bonuses()->create([
            'employee_id' => $employee1->id,
            'amount' => 1000,
            'description' => 'Performance Bonus',
            'date' => now(),
            'release_type' => 'with_salary',
        ]);
    }
}
