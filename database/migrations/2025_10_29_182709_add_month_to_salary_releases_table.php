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
            $table->string('month')->after('employee_id')->nullable();
            $table->decimal('partial_amount', 10, 2)->nullable()->after('total_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_releases', function (Blueprint $table) {
            $table->dropColumn(['month', 'partial_amount']);
        });
    }
};
