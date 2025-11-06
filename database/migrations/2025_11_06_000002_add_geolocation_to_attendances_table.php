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
        Schema::table('attendances', function (Blueprint $table) {
            // Check-in geolocation
            $table->decimal('check_in_latitude', 10, 8)->nullable()->after('check_in');
            $table->decimal('check_in_longitude', 11, 8)->nullable()->after('check_in_latitude');
            $table->string('check_in_ip', 45)->nullable()->after('check_in_longitude');
            $table->text('check_in_user_agent')->nullable()->after('check_in_ip');
            $table->decimal('check_in_distance_meters', 8, 2)->nullable()->after('check_in_user_agent');
            
            // Check-out geolocation
            $table->decimal('check_out_latitude', 10, 8)->nullable()->after('check_out');
            $table->decimal('check_out_longitude', 11, 8)->nullable()->after('check_out_latitude');
            $table->string('check_out_ip', 45)->nullable()->after('check_out_longitude');
            $table->text('check_out_user_agent')->nullable()->after('check_out_ip');
            $table->decimal('check_out_distance_meters', 8, 2)->nullable()->after('check_out_user_agent');
            
            // Indexes for querying
            $table->index('check_in_ip');
            $table->index('check_out_ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['check_in_ip']);
            $table->dropIndex(['check_out_ip']);
            
            $table->dropColumn([
                'check_in_latitude',
                'check_in_longitude',
                'check_in_ip',
                'check_in_user_agent',
                'check_in_distance_meters',
                'check_out_latitude',
                'check_out_longitude',
                'check_out_ip',
                'check_out_user_agent',
                'check_out_distance_meters',
            ]);
        });
    }
};
