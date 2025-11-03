<?php

namespace Database\Factories;

use App\Models\AttendanceFixRequest;
use App\Models\Attendance;
use App\Models\EmployeeUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AttendanceFixRequest>
 */
class AttendanceFixRequestFactory extends Factory
{
    protected $model = AttendanceFixRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $reasons = [
            'I forgot to check out yesterday. I left the office at around 6:00 PM.',
            'There was a system issue and I could not check in on time. I arrived at 9:00 AM.',
            'I had an emergency and had to leave early without checking out properly.',
            'The attendance system was down when I tried to check in this morning.',
            'I was in a meeting and forgot to check in. I actually arrived at 8:30 AM.',
            'Power outage prevented me from checking out. I left at 5:30 PM.',
            'My check-in time is incorrect. I actually arrived at 8:45 AM, not 10:00 AM.',
            'I checked in on the wrong date by mistake. Please correct this.',
        ];

        return [
            'employee_user_id' => EmployeeUser::factory(),
            'attendance_id' => Attendance::factory(),
            'reason' => $this->faker->randomElement($reasons),
            'status' => 'pending',
            'admin_notes' => null,
            'processed_by' => null,
            'processed_at' => null,
        ];
    }

    /**
     * Indicate that the fix request is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'admin_notes' => null,
            'processed_by' => null,
            'processed_at' => null,
        ]);
    }

    /**
     * Indicate that the fix request is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'admin_notes' => $this->faker->optional()->sentence(),
            'processed_by' => User::factory(),
            'processed_at' => now(),
        ]);
    }

    /**
     * Indicate that the fix request is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'admin_notes' => $this->faker->sentence(),
            'processed_by' => User::factory(),
            'processed_at' => now(),
        ]);
    }
}
