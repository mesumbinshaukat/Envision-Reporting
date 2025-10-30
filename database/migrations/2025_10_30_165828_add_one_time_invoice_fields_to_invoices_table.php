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
        Schema::table('invoices', function (Blueprint $table) {
            // Make client_id nullable for one-time invoices
            $table->foreignId('client_id')->nullable()->change();
            
            // Add fields for one-time invoices
            $table->boolean('is_one_time')->default(false)->after('client_id');
            $table->string('one_time_client_name')->nullable()->after('is_one_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['is_one_time', 'one_time_client_name']);
            $table->foreignId('client_id')->nullable(false)->change();
        });
    }
};
