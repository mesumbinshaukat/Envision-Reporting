<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceFixRequest;
use App\Models\EmployeeUser;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all employee users
        $employeeUsers = EmployeeUser::all();

        if ($employeeUsers->isEmpty()) {
            $this->command->warn('No employee users found. Please seed employee users first.');
            return;
        }

        $this->command->info('Seeding attendance records...');

        foreach ($employeeUsers as $employeeUser) {
            // Create attendance for the last 30 days
            for ($i = 30; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                
                // Skip weekends (optional - remove if you want weekend attendance)
                if ($date->isWeekend()) {
                    continue;
                }

                // 90% chance of having attendance
                if (rand(1, 100) <= 90) {
                    $checkIn = $date->copy()->setTime(
                        rand(8, 10),
                        rand(0, 59)
                    );

                    // 85% chance of checking out
                    $checkOut = rand(1, 100) <= 85
                        ? $checkIn->copy()->addHours(rand(7, 10))->addMinutes(rand(0, 59))
                        : null;

                    $attendance = Attendance::create([
                        'employee_user_id' => $employeeUser->id,
                        'attendance_date' => $date,
                        'check_in' => $checkIn,
                        'check_out' => $checkOut,
                    ]);

                    // 5% chance of having a fix request for incomplete attendance
                    if (!$checkOut && rand(1, 100) <= 5) {
                        AttendanceFixRequest::create([
                            'employee_user_id' => $employeeUser->id,
                            'attendance_id' => $attendance->id,
                            'reason' => 'I forgot to check out. I left the office at around ' . rand(5, 7) . ':' . str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT) . ' PM.',
                            'status' => 'pending',
                        ]);
                    }
                }
            }

            $this->command->info("Created attendance records for {$employeeUser->name}");
        }

        $this->command->info('Attendance seeding completed!');
    }
}
