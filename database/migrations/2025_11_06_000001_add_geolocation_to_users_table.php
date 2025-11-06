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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('office_latitude', 10, 8)->nullable()->after('password');
            $table->decimal('office_longitude', 11, 8)->nullable()->after('office_latitude');
            $table->integer('office_radius_meters')->default(15)->after('office_longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['office_latitude', 'office_longitude', 'office_radius_meters']);
        });
    }
};
