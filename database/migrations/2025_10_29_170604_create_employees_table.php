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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('marital_status')->nullable();
            $table->string('primary_contact');
            $table->string('email');
            $table->string('role');
            $table->string('secondary_contact')->nullable();
            $table->string('employment_type');
            $table->date('joining_date')->nullable();
            $table->date('last_date')->nullable();
            $table->decimal('salary', 10, 2);
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
