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
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_user_id')->constrained('employee_users')->onDelete('cascade');
            $table->foreignId('attendance_id')->nullable()->constrained('attendances')->onDelete('cascade');
            $table->enum('action', ['check_in_attempt', 'check_in_success', 'check_in_failed', 'check_out_attempt', 'check_out_success', 'check_out_failed']);
            $table->enum('failure_reason', ['out_of_range', 'already_checked_in', 'already_checked_out', 'not_checked_in', 'geolocation_denied', 'geolocation_unavailable', 'other'])->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('distance_from_office', 8, 2)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type', 50)->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('os', 100)->nullable();
            $table->text('additional_info')->nullable();
            $table->timestamp('attempted_at');
            $table->timestamps();
            
            // Indexes
            $table->index('employee_user_id');
            $table->index('attendance_id');
            $table->index('action');
            $table->index('attempted_at');
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
