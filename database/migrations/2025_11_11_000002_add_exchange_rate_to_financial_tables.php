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
        // Add exchange_rate_at_time to invoices table
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('exchange_rate_at_time', 12, 6)->nullable()->after('currency_id');
        });

        // Add exchange_rate_at_time to bonuses table
        Schema::table('bonuses', function (Blueprint $table) {
            $table->decimal('exchange_rate_at_time', 12, 6)->nullable()->after('currency_id');
        });

        // Add exchange_rate_at_time to expenses table
        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('exchange_rate_at_time', 12, 6)->nullable()->after('currency_id');
        });

        // Add exchange_rate_at_time to salary_releases table
        Schema::table('salary_releases', function (Blueprint $table) {
            $table->decimal('exchange_rate_at_time', 12, 6)->nullable()->after('currency_id');
        });

        // Backfill existing records with current conversion rates
        DB::statement('
            UPDATE invoices i
            JOIN currencies c ON i.currency_id = c.id
            SET i.exchange_rate_at_time = c.conversion_rate
            WHERE i.exchange_rate_at_time IS NULL
        ');

        DB::statement('
            UPDATE bonuses b
            JOIN currencies c ON b.currency_id = c.id
            SET b.exchange_rate_at_time = c.conversion_rate
            WHERE b.exchange_rate_at_time IS NULL
        ');

        DB::statement('
            UPDATE expenses e
            JOIN currencies c ON e.currency_id = c.id
            SET e.exchange_rate_at_time = c.conversion_rate
            WHERE e.exchange_rate_at_time IS NULL
        ');

        DB::statement('
            UPDATE salary_releases sr
            JOIN currencies c ON sr.currency_id = c.id
            SET sr.exchange_rate_at_time = c.conversion_rate
            WHERE sr.exchange_rate_at_time IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('exchange_rate_at_time');
        });

        Schema::table('bonuses', function (Blueprint $table) {
            $table->dropColumn('exchange_rate_at_time');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('exchange_rate_at_time');
        });

        Schema::table('salary_releases', function (Blueprint $table) {
            $table->dropColumn('exchange_rate_at_time');
        });
    }
};
