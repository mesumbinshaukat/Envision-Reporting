<?php

namespace Tests\Feature\Admin;

use App\Events\EmployeeActivityCreated;
use App\Models\Employee;
use App\Models\EmployeeActivityLog;
use App\Models\EmployeeUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure default user factory users have admin flag by default
        User::factory()->create();
    }

    public function test_admin_can_view_activity_logs_index(): void
    {
        $admin = User::factory()->create();
        $employee = Employee::factory()->for($admin, 'user')->create();
        $employeeUser = EmployeeUser::factory()
            ->for($employee)
            ->for($admin, 'admin')
            ->create();

        $log = EmployeeActivityLog::factory()
            ->forAdmin($admin)
            ->create([
                'employee_user_id' => $employeeUser->id,
                'action' => 'test_action',
                'occurred_at' => Carbon::now(),
            ]);

        $response = $this->actingAs($admin)->get(route('admin.activity-logs.index'));

        $response->assertOk();
        $response->assertSee($log->action);
        $response->assertSee($employeeUser->name);
    }

    public function test_employee_cannot_view_activity_logs(): void
    {
        $admin = User::factory()->create();
        $employee = Employee::factory()->for($admin, 'user')->create();
        $employeeUser = EmployeeUser::factory()
            ->for($employee)
            ->for($admin, 'admin')
            ->create();

        $response = $this->actingAs($employeeUser, guard: 'employee')
            ->get(route('admin.activity-logs.index'));

        $response->assertForbidden();
    }

    public function test_activity_log_show_requires_admin_ownership(): void
    {
        $admin = User::factory()->create();
        $otherAdmin = User::factory()->create();

        $log = EmployeeActivityLog::factory()->forAdmin($otherAdmin)->create();

        $response = $this->actingAs($admin)->get(route('admin.activity-logs.show', $log));

        $response->assertForbidden();
    }

    public function test_broadcast_event_payload_contains_expected_fields(): void
    {
        Event::fake();

        $admin = User::factory()->create();
        $employee = Employee::factory()->for($admin, 'user')->create();
        $employeeUser = EmployeeUser::factory()
            ->for($employee)
            ->for($admin, 'admin')
            ->create();

        $log = EmployeeActivityLog::factory()
            ->forAdmin($admin)
            ->create([
                'employee_user_id' => $employeeUser->id,
            ]);

        event(new EmployeeActivityCreated($log->fresh(['employeeUser.employee', 'admin'])));

        Event::assertDispatched(EmployeeActivityCreated::class, function (EmployeeActivityCreated $event) use ($log) {
            $payload = $event->broadcastWith();

            return $payload['id'] === $log->id
                && $payload['employee_user']['id'] === $log->employee_user_id
                && $payload['action'] === $log->action;
        });
    }
}
