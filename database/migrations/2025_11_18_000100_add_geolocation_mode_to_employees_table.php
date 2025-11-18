<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('geolocation_mode', 40)
                ->default('required')
                ->after('geolocation_required');
        });

        DB::table('employees')
            ->where('geolocation_required', false)
            ->update(['geolocation_mode' => 'disabled']);

        DB::table('employees')
            ->where('geolocation_required', true)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('employee_ip_whitelists')
                    ->whereColumn('employee_ip_whitelists.employee_id', 'employees.id');
            })
            ->update(['geolocation_mode' => 'required_with_whitelist']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('geolocation_mode');
        });
    }
};
