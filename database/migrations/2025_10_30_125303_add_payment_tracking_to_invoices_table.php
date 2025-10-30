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
            $table->decimal('paid_amount', 10, 2)->default(0)->after('amount');
            $table->decimal('remaining_amount', 10, 2)->default(0)->after('paid_amount');
            $table->date('payment_date')->nullable()->after('due_date');
            $table->string('payment_month')->nullable()->after('payment_date'); // Format: YYYY-MM
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'remaining_amount', 'payment_date', 'payment_month']);
        });
    }
};
