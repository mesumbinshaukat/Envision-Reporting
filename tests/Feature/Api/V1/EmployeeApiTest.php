<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Employee;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token', ['admin'])->plainTextToken;
        $this->currency = Currency::factory()->create(['user_id' => $this->user->id, 'is_base' => true]);
    }

    public function test_admin_can_list_employees()
    {
        Employee::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/employees');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['current_page', 'total', 'per_page'],
                'links',
            ])
            ->assertJson(['success' => true]);
    }

    public function test_admin_can_create_employee()
    {
        $employeeData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'primary_contact' => '+1234567890',
            'role' => 'Developer',
            'employment_type' => 'full_time',
            'joining_date' => '2024-01-01',
            'salary' => 50000,
            'commission_rate' => 5,
            'currency_id' => $this->currency->id,
            'geolocation_mode' => 'required',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/employees', $employeeData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Employee created successfully',
            ])
            ->assertJsonPath('data.name', 'John Doe');

        $this->assertDatabaseHas('employees', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_admin_can_view_employee()
    {
        $employee = Employee::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/employees/' . $employee->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                ],
            ]);
    }

    public function test_admin_can_update_employee()
    {
        $employee = Employee::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/v1/employees/' . $employee->id, [
                'name' => 'Updated Name',
                'salary' => 60000,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Employee updated successfully',
            ]);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'name' => 'Updated Name',
            'salary' => 60000,
        ]);
    }

    public function test_admin_can_delete_employee()
    {
        $employee = Employee::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/v1/employees/' . $employee->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Employee deleted successfully',
            ]);

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    public function test_employee_cannot_access_employee_endpoints()
    {
        $employeeUser = \App\Models\EmployeeUser::factory()->create(['admin_id' => $this->user->id]);
        $employeeToken = $employeeUser->createToken('employee-token', ['employee'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $employeeToken)
            ->getJson('/api/v1/employees');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Only admin users can access employee list',
            ]);
    }

    public function test_admin_can_toggle_employee_geolocation()
    {
        $employee = Employee::factory()->create([
            'user_id' => $this->user->id,
            'geolocation_mode' => 'disabled',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/employees/' . $employee->id . '/toggle-geolocation', [
                'geolocation_mode' => 'required',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Geolocation mode updated successfully',
            ]);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'geolocation_mode' => 'required',
        ]);
    }

    public function test_admin_can_perform_bulk_delete()
    {
        $employees = Employee::factory()->count(3)->create(['user_id' => $this->user->id]);
        $employeeIds = $employees->pluck('id')->toArray();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/employees/bulk-action', [
                'action' => 'delete',
                'employee_ids' => $employeeIds,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Employees deleted successfully',
            ]);

        foreach ($employeeIds as $id) {
            $this->assertSoftDeleted('employees', ['id' => $id]);
        }
    }

    public function test_pagination_works_correctly()
    {
        Employee::factory()->count(25)->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/employees?per_page=10&page=2');

        $response->assertStatus(200)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonCount(10, 'data');
    }

    public function test_filtering_works_correctly()
    {
        Employee::factory()->create(['user_id' => $this->user->id, 'role' => 'Developer']);
        Employee::factory()->create(['user_id' => $this->user->id, 'role' => 'Designer']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/employees?filter[role]=Developer');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.role', 'Developer');
    }

    public function test_sorting_works_correctly()
    {
        Employee::factory()->create(['user_id' => $this->user->id, 'name' => 'Zoe']);
        Employee::factory()->create(['user_id' => $this->user->id, 'name' => 'Alice']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/employees?sort=name&direction=asc');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Alice')
            ->assertJsonPath('data.1.name', 'Zoe');
    }
}
