<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('is_sales_person')->default(false)->after('commission_rate');
        });

        DB::table('employees')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('employee_users')
                    ->whereColumn('employee_users.employee_id', 'employees.id');
            })
            ->update(['is_sales_person' => true]);
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('is_sales_person');
        });
    }
};
