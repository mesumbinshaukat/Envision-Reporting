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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Admin who owns this currency
            $table->string('code', 10); // USD, PKR, GBP, etc.
            $table->string('name'); // US Dollar, Pakistani Rupee, etc.
            $table->string('symbol', 10); // $, Rs, £, etc.
            $table->string('country'); // United States, Pakistan, etc.
            $table->decimal('conversion_rate', 20, 6)->default(1); // Rate to base currency
            $table->boolean('is_base')->default(false); // Is this the base currency?
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Ensure only one base currency per user
            $table->unique(['user_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
