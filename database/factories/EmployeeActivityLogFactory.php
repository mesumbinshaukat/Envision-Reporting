<?php

namespace Database\Factories;

use App\Models\EmployeeActivityLog;
use App\Models\EmployeeUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmployeeActivityLog>
 */
class EmployeeActivityLogFactory extends Factory
{
    protected $model = EmployeeActivityLog::class;

    public function definition(): array
    {
        $actions = ['auth_login_success', 'client_create', 'invoice_view', 'navigation_dashboard'];
        $categories = ['auth', 'clients', 'invoices', 'navigation'];

        return [
            'admin_id' => User::factory(),
            'employee_user_id' => EmployeeUser::factory(),
            'category' => $this->faker->randomElement($categories),
            'action' => $this->faker->randomElement($actions),
            'summary' => $this->faker->sentence(),
            'description' => $this->faker->optional()->paragraph(),
            'request_method' => $this->faker->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
            'route_name' => $this->faker->optional()->slug(),
            'request_path' => '/' . $this->faker->slug(),
            'referer' => $this->faker->optional()->url(),
            'response_status' => $this->faker->optional()->numberBetween(200, 500),
            'ip_address' => $this->faker->ipv4(),
            'ip_address_v4' => $this->faker->ipv4(),
            'ip_address_v6' => $this->faker->optional()->ipv6(),
            'device_type' => $this->faker->randomElement(['Desktop', 'Mobile', 'Tablet']),
            'browser' => $this->faker->userAgent(),
            'os' => $this->faker->randomElement(['Windows 11', 'macOS Sonoma', 'Ubuntu 24.04']),
            'user_agent' => $this->faker->userAgent(),
            'request_payload' => $this->faker->optional()->randomElements([
                'foo' => 'bar',
                'page' => 1,
                'filters' => ['status' => 'active'],
            ]),
            'metadata' => ['depth' => $this->faker->numberBetween(1, 5)],
            'occurred_at' => now(),
        ];
    }
 
    public function forAdmin(User $admin): static
    {
        return $this->state(fn () => [
            'admin_id' => $admin->id,
            'employee_user_id' => EmployeeUser::factory()->withAdmin($admin),
        ]);
    }
}
