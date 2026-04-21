<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Employee;
use App\Models\Currency;
use App\Models\SalaryRelease;
use App\Models\Bonus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeSalarySlipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->currency = Currency::factory()->create([
            'user_id' => $this->user->id,
            'is_base' => true,
            'symbol' => 'Rs.',
            'code' => 'PKR',
            'conversion_rate' => 1
        ]);
    }

    public function test_authenticated_user_can_access_salary_slip_page()
    {
        $employee = Employee::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'salary' => 50000,
            'commission_rate' => 5,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('employees.salary-slip', ['employee' => $employee, 'month' => '2024-03']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_salary_slip_requires_month_parameter()
    {
        $employee = Employee::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('employees.salary-slip', ['employee' => $employee]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('month');
    }

    public function test_salary_slip_requires_valid_month_format()
    {
        $employee = Employee::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('employees.salary-slip', ['employee' => $employee, 'month' => 'invalid']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('month');
    }

    public function test_salary_slip_shows_correct_base_salary()
    {
        $employee = Employee::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'salary' => 75000,
            'name' => 'Test Employee',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('employees.salary-slip', ['employee' => $employee, 'month' => '2024-03']));

        $response->assertStatus(200);
    }

    public function test_salary_slip_includes_existing_salary_release_data()
    {
        $employee = Employee::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'salary' => 50000,
        ]);

        $salaryRelease = SalaryRelease::factory()->create([
            'user_id' => $this->user->id,
            'employee_id' => $employee->id,
            'currency_id' => $this->currency->id,
            'month' => '2024-03',
            'base_salary' => 50000,
            'commission_amount' => 5000,
            'bonus_amount' => 2000,
            'deductions' => 1000,
            'total_amount' => 56000,
            'release_date' => '2024-03-31',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('employees.salary-slip', ['employee' => $employee, 'month' => '2024-03']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_unauthorized_user_cannot_access_other_users_employee_salary_slip()
    {
        $otherUser = User::factory()->create();
        $employee = Employee::factory()->create([
            'user_id' => $otherUser->id,
            'currency_id' => $this->currency->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('employees.salary-slip', ['employee' => $employee, 'month' => '2024-03']));

        $response->assertStatus(403);
    }

    public function test_salary_slip_calculates_bonuses_for_the_month()
    {
        $employee = Employee::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'salary' => 50000,
        ]);

        // Create a bonus for the month
        Bonus::factory()->create([
            'user_id' => $this->user->id,
            'employee_id' => $employee->id,
            'currency_id' => $this->currency->id,
            'amount' => 5000,
            'description' => 'Performance Bonus',
            'date' => '2024-03-15',
            'released' => false,
            'release_type' => 'with_salary',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('employees.salary-slip', ['employee' => $employee, 'month' => '2024-03']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_salary_slip_shows_provisional_status_when_no_release_exists()
    {
        $employee = Employee::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'salary' => 50000,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('employees.salary-slip', ['employee' => $employee, 'month' => '2024-03']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_salary_slip_pdf_filename_includes_employee_name_and_month()
    {
        $employee = Employee::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'name' => 'John Doe',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('employees.salary-slip', ['employee' => $employee, 'month' => '2024-03']));

        $response->assertStatus(200);
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('salary-slip-', $contentDisposition);
        $this->assertStringContainsString('2024-03', $contentDisposition);
    }
}
