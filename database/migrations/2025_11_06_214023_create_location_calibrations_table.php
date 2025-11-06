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
        Schema::create('location_calibrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('label')->default('Office Location');
            
            // Known accurate location (set by admin)
            $table->decimal('known_latitude', 10, 8);
            $table->decimal('known_longitude', 11, 8);
            
            // GPS reading at that location
            $table->decimal('gps_latitude', 10, 8);
            $table->decimal('gps_longitude', 11, 8);
            $table->decimal('gps_accuracy', 10, 2)->nullable();
            
            // Calculated offset
            $table->decimal('latitude_offset', 10, 8);
            $table->decimal('longitude_offset', 11, 8);
            
            $table->boolean('is_active')->default(true);
            $table->text('device_info')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_calibrations');
    }
};
