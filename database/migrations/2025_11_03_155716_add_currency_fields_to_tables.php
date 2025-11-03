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
        // Add currency to invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
        });
        
        // Add currency to employees
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
        });
        
        // Add currency to expenses
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
        });
        
        // Add currency to bonuses
        Schema::table('bonuses', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
        });
        
        // Add currency to salary_releases
        Schema::table('salary_releases', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn('currency_id');
        });
        
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn('currency_id');
        });
        
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn('currency_id');
        });
        
        Schema::table('bonuses', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn('currency_id');
        });
        
        Schema::table('salary_releases', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn('currency_id');
        });
    }
};
