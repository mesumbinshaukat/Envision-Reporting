<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmployeeUser>
 */
class EmployeeUserFactory extends Factory
{
    protected $model = EmployeeUser::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'admin_id' => null,
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
        ];
    }

    public function withAdmin(User $admin): static
    {
        return $this->state(fn () => [
            'employee_id' => Employee::factory()->for($admin, 'user'),
            'admin_id' => $admin->id,
        ]);
    }

    public function configure()
    {
        return $this->afterMaking(function (EmployeeUser $employeeUser) {
            if (!$employeeUser->admin_id && $employeeUser->employee_id) {
                $employeeUser->admin_id = Employee::find($employeeUser->employee_id)?->user_id;
            }
        })->afterCreating(function (EmployeeUser $employeeUser) {
            if (!$employeeUser->admin_id && $employeeUser->employee_id) {
                $adminId = Employee::find($employeeUser->employee_id)?->user_id;
                if ($adminId) {
                    $employeeUser->forceFill(['admin_id' => $adminId])->saveQuietly();
                }
            }
        });
    }
}
