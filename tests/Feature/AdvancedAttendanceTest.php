<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeUser;
use App\Models\OfficeSchedule;
use App\Models\User;
use App\Models\Currency;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Employee $employee;
    protected EmployeeUser $employeeUser;
    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->currency = Currency::factory()->create([
            'user_id' => $this->admin->id,
            'is_base' => true,
        ]);

        $this->employee = Employee::factory()->create([
            'user_id' => $this->admin->id,
            'currency_id' => $this->currency->id,
            'salary' => 30000,
            'max_monthly_leaves' => 1,
        ]);

        $this->employeeUser = EmployeeUser::factory()->create([
            'employee_id' => $this->employee->id,
            'admin_id' => $this->admin->id,
            'email' => $this->employee->email,
        ]);

        // Setup global office schedule
        OfficeSchedule::create([
            'user_id' => $this->admin->id,
            'start_time' => '09:00',
            'end_time' => '18:00',
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
            'grace_time_minutes' => 30,
            'late_count_for_deduction' => 3,
            'salary_divisor' => 30,
            'timezone' => 'UTC',
        ]);
    }

    public function test_late_detection_with_grace_time(): void
    {
        // 1. Not late (within grace time: 09:15)
        Carbon::setTestNow(Carbon::parse('2026-04-27 09:15:00', 'UTC')); // Monday
        
        $response = $this->actingAs($this->employeeUser, 'employee')
            ->postJson(route('attendance.check-in'), [
                'latitude' => 0,
                'longitude' => 0,
            ]);
            
        $response->assertOk();

        $this->assertDatabaseHas('attendances', [
            'employee_user_id' => $this->employeeUser->id,
            'is_late' => false,
        ]);

        // 2. Late (after grace time: 09:35)
        Attendance::query()->delete();
        Carbon::setTestNow(Carbon::parse('2026-04-28 09:35:00', 'UTC')); // Tuesday
        
        $response = $this->actingAs($this->employeeUser, 'employee')
            ->postJson(route('attendance.check-in'), [
                'latitude' => 0,
                'longitude' => 0,
            ]);
            
        $response->assertOk();

        $this->assertDatabaseHas('attendances', [
            'employee_user_id' => $this->employeeUser->id,
            'is_late' => true,
            'late_minutes' => 35,
        ]);
    }

    public function test_employee_specific_schedule_overrides_global(): void
    {
        // Set specific schedule for Wednesday: 11:00 AM
        $this->employee->schedules()->create([
            'day_of_week' => 'wednesday',
            'start_time' => '11:00',
            'end_time' => '19:00',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-04-29 10:30:00', 'UTC')); // Wednesday
        
        $response = $this->actingAs($this->employeeUser, 'employee')
            ->postJson(route('attendance.check-in'), [
                'latitude' => 0,
                'longitude' => 0,
            ]);
            
        $response->assertOk();

        // 10:30 is before 11:00, so not late
        $this->assertDatabaseHas('attendances', [
            'employee_user_id' => $this->employeeUser->id,
            'is_late' => false,
        ]);

        // Check late for specific schedule (11:45 is > 11:00 + 30m grace)
        Attendance::query()->delete();
        Carbon::setTestNow(Carbon::parse('2026-04-29 11:45:00', 'UTC')); // Wednesday
        
        $response = $this->actingAs($this->employeeUser, 'employee')
            ->postJson(route('attendance.check-in'), [
                'latitude' => 0,
                'longitude' => 0,
            ]);
            
        $response->assertOk();

        $this->assertDatabaseHas('attendances', [
            'employee_user_id' => $this->employeeUser->id,
            'is_late' => true,
            'late_minutes' => 45,
        ]);
    }

    public function test_payroll_late_deductions(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-01 09:00:00', 'UTC'));
        
        // Create 3 lates in April
        Attendance::create([
            'employee_user_id' => $this->employeeUser->id,
            'attendance_date' => '2026-04-01',
            'check_in' => '2026-04-01 10:00:00',
            'is_late' => true,
        ]);
        Attendance::create([
            'employee_user_id' => $this->employeeUser->id,
            'attendance_date' => '2026-04-02',
            'check_in' => '2026-04-02 10:00:00',
            'is_late' => true,
        ]);
        Attendance::create([
            'employee_user_id' => $this->employeeUser->id,
            'attendance_date' => '2026-04-03',
            'check_in' => '2026-04-03 10:00:00',
            'is_late' => true,
        ]);

        $this->actingAs($this->admin);
        
        $response = $this->postJson(route('salary-releases.preview'), [
            'employee_id' => $this->employee->id,
            'month' => '2026-04',
        ]);

        $response->assertOk();
        // 30000 / 30 = 1000 per day. 3 lates = 1 day deduction.
        $response->assertJsonPath('late_deduction', '1,000.00');
    }

    public function test_payroll_leave_deductions(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-01 09:00:00', 'UTC'));

        // Expected working days in April 2026 (Mon-Sat): 26 days
        // Present for only 20 days. Leaves = 6.
        // Max monthly leaves = 1. Extra leaves = 5.
        // Deduction = 5 * 1000 = 5000.

        for ($i = 1; $i <= 20; $i++) {
            Attendance::create([
                'employee_user_id' => $this->employeeUser->id,
                'attendance_date' => '2026-04-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'check_in' => '2026-04-01 09:00:00',
                'is_late' => false,
            ]);
        }

        $this->actingAs($this->admin);
        
        $response = $this->postJson(route('salary-releases.preview'), [
            'employee_id' => $this->employee->id,
            'month' => '2026-04',
        ]);

        $response->assertOk();
        $response->assertJsonPath('leave_deduction', '5,000.00');
    }
}
