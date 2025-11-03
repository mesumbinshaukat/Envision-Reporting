<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\EmployeeUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $attendanceDate = $this->faker->dateTimeBetween('-30 days', 'now');
        $checkIn = Carbon::instance($attendanceDate)->setTime(
            $this->faker->numberBetween(8, 10),
            $this->faker->numberBetween(0, 59)
        );
        
        // 80% chance of having a check-out
        $checkOut = $this->faker->boolean(80) 
            ? $checkIn->copy()->addHours($this->faker->numberBetween(7, 10))->addMinutes($this->faker->numberBetween(0, 59))
            : null;

        return [
            'employee_user_id' => EmployeeUser::factory(),
            'attendance_date' => $attendanceDate,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
        ];
    }

    /**
     * Indicate that the attendance is complete (has both check-in and check-out).
     */
    public function complete(): static
    {
        return $this->state(function (array $attributes) {
            $checkIn = Carbon::parse($attributes['check_in']);
            return [
                'check_out' => $checkIn->copy()->addHours($this->faker->numberBetween(7, 10))->addMinutes($this->faker->numberBetween(0, 59)),
            ];
        });
    }

    /**
     * Indicate that the attendance only has check-in.
     */
    public function checkedInOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'check_out' => null,
        ]);
    }

    /**
     * Indicate that the attendance is for today.
     */
    public function today(): static
    {
        return $this->state(function (array $attributes) {
            $checkIn = Carbon::today()->setTime(
                $this->faker->numberBetween(8, 10),
                $this->faker->numberBetween(0, 59)
            );
            
            return [
                'attendance_date' => Carbon::today(),
                'check_in' => $checkIn,
            ];
        });
    }
}
