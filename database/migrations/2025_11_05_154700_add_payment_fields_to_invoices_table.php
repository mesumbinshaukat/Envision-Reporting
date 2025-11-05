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
            $table->json('attachments')->nullable()->after('special_note');
            $table->string('payment_method')->nullable()->after('attachments');
            $table->string('custom_payment_method')->nullable()->after('payment_method');
            $table->decimal('payment_processing_fee', 10, 2)->default(0)->after('custom_payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['attachments', 'payment_method', 'custom_payment_method', 'payment_processing_fee']);
        });
    }
};
