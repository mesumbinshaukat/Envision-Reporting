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
            if (!Schema::hasColumn('invoices', 'approval_status')) {
                $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('approved')->after('status');
            }
            if (!Schema::hasColumn('invoices', 'created_by_employee_id')) {
                $table->foreignId('created_by_employee_id')->nullable()->after('employee_id')->constrained('employee_users')->onDelete('set null');
            }
            if (!Schema::hasColumn('invoices', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approval_status');
            }
            if (!Schema::hasColumn('invoices', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->onDelete('set null');
            }
            // invoices table already has soft deletes
        });
        
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'created_by_employee_id')) {
                $table->foreignId('created_by_employee_id')->nullable()->after('user_id')->constrained('employee_users')->onDelete('set null');
            }
            if (!Schema::hasColumn('clients', 'deleted_by_employee_id')) {
                $table->foreignId('deleted_by_employee_id')->nullable()->after('created_by_employee_id')->constrained('employee_users')->onDelete('set null');
            }
            if (!Schema::hasColumn('clients', 'deleted_at')) {
                $table->softDeletes(); // For trash functionality
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['approval_status', 'created_by_employee_id', 'approved_at', 'approved_by']);
        });
        
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['created_by_employee_id', 'deleted_by_employee_id']);
            $table->dropSoftDeletes();
        });
    }
};
