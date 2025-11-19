<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->name(),
            'marital_status' => $this->faker->randomElement(['single', 'married', 'divorced']),
            'primary_contact' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'role' => $this->faker->randomElement(['Developer', 'Designer', 'Manager']),
            'secondary_contact' => $this->faker->optional()->phoneNumber(),
            'employment_type' => $this->faker->randomElement(['full_time', 'contract']),
            'joining_date' => $this->faker->dateTimeBetween('-3 years', 'now'),
            'last_date' => null,
            'salary' => $this->faker->numberBetween(50000, 200000),
            'commission_rate' => $this->faker->randomFloat(2, 0, 10),
        ];
    }
}
