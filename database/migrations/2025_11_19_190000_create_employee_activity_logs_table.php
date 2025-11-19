<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('employee_user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 100)->nullable()->index();
            $table->string('action', 150);
            $table->string('summary')->nullable();
            $table->text('description')->nullable();
            $table->string('request_method', 10)->nullable();
            $table->string('route_name')->nullable()->index();
            $table->string('request_path')->nullable();
            $table->string('referer')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('ip_address_v4')->nullable();
            $table->string('ip_address_v6')->nullable();
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamps();

            $table->index(['employee_user_id', 'occurred_at']);
            $table->index(['admin_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_activity_logs');
    }
};
