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
        Schema::table('salary_releases', function (Blueprint $table) {
            $table->decimal('late_deduction', 15, 2)->default(0)->after('deductions');
            $table->decimal('leave_deduction', 15, 2)->default(0)->after('late_deduction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_releases', function (Blueprint $table) {
            $table->dropColumn(['late_deduction', 'leave_deduction']);
        });
    }
};
