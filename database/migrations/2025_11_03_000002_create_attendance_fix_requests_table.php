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
        Schema::create('attendance_fix_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_user_id')->constrained('employee_users')->onDelete('cascade');
            $table->foreignId('attendance_id')->constrained('attendances')->onDelete('cascade');
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('employee_user_id');
            $table->index('attendance_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_fix_requests');
    }
};
