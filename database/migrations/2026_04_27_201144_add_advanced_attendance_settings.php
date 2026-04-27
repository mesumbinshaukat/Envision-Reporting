<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('office_schedules', function (Blueprint $table) {
            $table->integer('grace_time_minutes')->default(0)->after('working_days');
            $table->integer('late_count_for_deduction')->default(3)->after('grace_time_minutes');
            $table->integer('salary_divisor')->default(30)->after('late_count_for_deduction');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->integer('max_monthly_leaves')->default(0)->after('salary');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->boolean('is_late')->default(false)->after('attendance_date');
            $table->integer('late_minutes')->default(0)->after('is_late');
        });

        Schema::create('employee_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('day_of_week'); // monday, tuesday, etc.
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_schedules');

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['is_late', 'late_minutes']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('max_monthly_leaves');
        });

        Schema::table('office_schedules', function (Blueprint $table) {
            $table->dropColumn(['grace_time_minutes', 'late_count_for_deduction', 'salary_divisor']);
        });
    }
};
