<?php

namespace Tests\Feature;

use App\Models\AllowanceType;
use App\Models\Employee;
use App\Models\EmployeeAllowance;
use App\Models\User;
use App\Models\Currency;
use App\Models\SalaryRelease;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllowanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Currency $currency;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->currency = Currency::factory()->create([
            'user_id' => $this->user->id,
            'code' => 'PKR',
            'symbol' => 'Rs.',
            'is_base' => true,
            'conversion_rate' => 1.0,
        ]);
        
        $this->employee = Employee::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'salary' => 50000,
        ]);
    }

    public function test_allowance_type_can_be_created(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('allowance-types.store'), [
            'name' => 'petrol',
            'label' => 'Petrol Allowance',
            'description' => 'Monthly petrol allowance for employees',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('allowance-types.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('allowance_types', [
            'name' => 'petrol',
            'label' => 'Petrol Allowance',
        ]);
    }

    public function test_allowance_type_name_must_be_unique(): void
    {
        $this->actingAs($this->user);

        AllowanceType::create([
            'name' => 'petrol',
            'label' => 'Petrol Allowance',
        ]);

        $response = $this->post(route('allowance-types.store'), [
            'name' => 'petrol',
            'label' => 'Duplicate Petrol',
            'is_active' => true,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_employee_allowance_can_be_created(): void
    {
        $this->actingAs($this->user);

        $allowanceType = AllowanceType::create([
            'name' => 'petrol',
            'label' => 'Petrol Allowance',
        ]);

        $response = $this->post(route('employee-allowances.store'), [
            'employee_id' => $this->employee->id,
            'allowance_type_id' => $allowanceType->id,
            'currency_id' => $this->currency->id,
            'amount' => 5000,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('employee-allowances.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('employee_allowances', [
            'employee_id' => $this->employee->id,
            'allowance_type_id' => $allowanceType->id,
            'amount' => 5000,
            'is_active' => true,
        ]);
    }

    public function test_duplicate_employee_allowance_cannot_be_created(): void
    {
        $this->actingAs($this->user);

        $allowanceType = AllowanceType::create([
            'name' => 'petrol',
            'label' => 'Petrol Allowance',
        ]);

        EmployeeAllowance::create([
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'allowance_type_id' => $allowanceType->id,
            'currency_id' => $this->currency->id,
            'amount' => 5000,
            'is_active' => true,
        ]);

        $response = $this->post(route('employee-allowances.store'), [
            'employee_id' => $this->employee->id,
            'allowance_type_id' => $allowanceType->id,
            'currency_id' => $this->currency->id,
            'amount' => 3000,
            'is_active' => true,
        ]);

        $response->assertSessionHasErrors('allowance_type_id');
    }

    public function test_allowances_are_included_in_salary_release_preview(): void
    {
        $this->actingAs($this->user);

        $allowanceType = AllowanceType::create([
            'name' => 'petrol',
            'label' => 'Petrol Allowance',
        ]);

        EmployeeAllowance::create([
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'allowance_type_id' => $allowanceType->id,
            'currency_id' => $this->currency->id,
            'amount' => 5000,
            'is_active' => true,
        ]);

        $response = $this->postJson(route('salary-releases.preview'), [
            'employee_id' => $this->employee->id,
            'month' => date('Y-m'),
        ]);

        $response->assertOk();
        $response->assertJsonPath('allowance_amount', '5,000.00');
        $response->assertJsonPath('total_calculated', '55,000.00'); // 50000 salary + 5000 allowance
    }

    public function test_allowances_are_included_in_salary_release_store(): void
    {
        $this->actingAs($this->user);

        $allowanceType = AllowanceType::create([
            'name' => 'petrol',
            'label' => 'Petrol Allowance',
        ]);

        EmployeeAllowance::create([
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'allowance_type_id' => $allowanceType->id,
            'currency_id' => $this->currency->id,
            'amount' => 5000,
            'is_active' => true,
        ]);

        $month = date('Y-m');
        $response = $this->post(route('salary-releases.store'), [
            'employee_id' => $this->employee->id,
            'month' => $month,
            'release_date' => date('Y-m-d'),
            'deductions' => 0,
        ]);

        $response->assertRedirect(route('salary-releases.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('salary_releases', [
            'employee_id' => $this->employee->id,
            'month' => $month,
            'base_salary' => 50000,
            'allowance_amount' => 5000,
            'total_amount' => 55000,
        ]);
    }

    public function test_inactive_allowances_are_not_included_in_salary(): void
    {
        $this->actingAs($this->user);

        $allowanceType = AllowanceType::create([
            'name' => 'petrol',
            'label' => 'Petrol Allowance',
        ]);

        EmployeeAllowance::create([
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'allowance_type_id' => $allowanceType->id,
            'currency_id' => $this->currency->id,
            'amount' => 5000,
            'is_active' => false, // Inactive
        ]);

        $response = $this->postJson(route('salary-releases.preview'), [
            'employee_id' => $this->employee->id,
            'month' => date('Y-m'),
        ]);

        $response->assertOk();
        $response->assertJsonPath('allowance_amount', '0.00');
        $response->assertJsonPath('total_calculated', '50,000.00'); // No allowance
    }

    public function test_allowance_type_can_be_updated(): void
    {
        $this->actingAs($this->user);

        $allowanceType = AllowanceType::create([
            'name' => 'petrol',
            'label' => 'Petrol Allowance',
        ]);

        $response = $this->put(route('allowance-types.update', $allowanceType), [
            'name' => 'petrol',
            'label' => 'Updated Petrol Allowance',
            'description' => 'Updated description',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('allowance-types.index'));
        
        $this->assertDatabaseHas('allowance_types', [
            'id' => $allowanceType->id,
            'label' => 'Updated Petrol Allowance',
        ]);
    }

    public function test_employee_allowance_can_be_updated(): void
    {
        $this->actingAs($this->user);

        $allowanceType = AllowanceType::create([
            'name' => 'petrol',
            'label' => 'Petrol Allowance',
        ]);

        $employeeAllowance = EmployeeAllowance::create([
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'allowance_type_id' => $allowanceType->id,
            'currency_id' => $this->currency->id,
            'amount' => 5000,
            'is_active' => true,
        ]);

        $response = $this->put(route('employee-allowances.update', $employeeAllowance), [
            'employee_id' => $this->employee->id,
            'allowance_type_id' => $allowanceType->id,
            'currency_id' => $this->currency->id,
            'amount' => 7000,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('employee-allowances.index'));
        
        $this->assertDatabaseHas('employee_allowances', [
            'id' => $employeeAllowance->id,
            'amount' => 7000,
        ]);
    }

    public function test_employee_allowance_can_be_deleted(): void
    {
        $this->actingAs($this->user);

        $allowanceType = AllowanceType::create([
            'name' => 'petrol',
            'label' => 'Petrol Allowance',
        ]);

        $employeeAllowance = EmployeeAllowance::create([
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'allowance_type_id' => $allowanceType->id,
            'currency_id' => $this->currency->id,
            'amount' => 5000,
            'is_active' => true,
        ]);

        $response = $this->delete(route('employee-allowances.destroy', $employeeAllowance));

        $response->assertRedirect(route('employee-allowances.index'));
        
        $this->assertSoftDeleted('employee_allowances', [
            'id' => $employeeAllowance->id,
        ]);
    }

    public function test_allowance_type_cannot_be_deleted_if_assigned(): void
    {
        $this->actingAs($this->user);

        $allowanceType = AllowanceType::create([
            'name' => 'petrol',
            'label' => 'Petrol Allowance',
        ]);

        EmployeeAllowance::create([
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'allowance_type_id' => $allowanceType->id,
            'currency_id' => $this->currency->id,
            'amount' => 5000,
            'is_active' => true,
        ]);

        $response = $this->delete(route('allowance-types.destroy', $allowanceType));

        $response->assertRedirect(route('allowance-types.index'));
        $response->assertSessionHas('error');
        
        $this->assertDatabaseHas('allowance_types', [
            'id' => $allowanceType->id,
        ]);
    }

    public function test_multiple_allowances_are_summed_correctly(): void
    {
        $this->actingAs($this->user);

        $petrol = AllowanceType::create([
            'name' => 'petrol',
            'label' => 'Petrol Allowance',
        ]);

        $housing = AllowanceType::create([
            'name' => 'housing',
            'label' => 'Housing Allowance',
        ]);

        EmployeeAllowance::create([
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'allowance_type_id' => $petrol->id,
            'currency_id' => $this->currency->id,
            'amount' => 5000,
            'is_active' => true,
        ]);

        EmployeeAllowance::create([
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'allowance_type_id' => $housing->id,
            'currency_id' => $this->currency->id,
            'amount' => 10000,
            'is_active' => true,
        ]);

        $response = $this->postJson(route('salary-releases.preview'), [
            'employee_id' => $this->employee->id,
            'month' => date('Y-m'),
        ]);

        $response->assertOk();
        $response->assertJsonPath('allowance_amount', '15,000.00'); // 5000 + 10000
        $response->assertJsonPath('total_calculated', '65,000.00'); // 50000 + 15000
    }
}
