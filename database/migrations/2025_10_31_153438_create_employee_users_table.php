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
        Schema::create('employee_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade'); // The admin who created this employee user
            $table->string('email')->unique();
            $table->string('password');
            $table->string('name'); // Cached from employee for faster queries
            $table->rememberToken();
            $table->timestamps();
            
            $table->index('email'); // For fast login queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_users');
    }
};
