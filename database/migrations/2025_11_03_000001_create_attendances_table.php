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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_user_id')->constrained('employee_users')->onDelete('cascade');
            $table->dateTime('check_in')->nullable();
            $table->dateTime('check_out')->nullable();
            $table->date('attendance_date');
            $table->timestamps();

            // Indexes for performance
            $table->index('employee_user_id');
            $table->index('attendance_date');
            $table->index(['employee_user_id', 'attendance_date']);
            
            // Ensure one attendance record per employee per day
            $table->unique(['employee_user_id', 'attendance_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
