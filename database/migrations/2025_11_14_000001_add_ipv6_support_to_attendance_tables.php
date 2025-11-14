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
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->string('ip_address_v4', 45)->nullable()->after('ip_address');
            $table->string('ip_address_v6', 45)->nullable()->after('ip_address_v4');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->string('check_in_ip_v6', 45)->nullable()->after('check_in_ip');
            $table->string('check_out_ip_v6', 45)->nullable()->after('check_out_ip');
        });

        DB::table('attendance_logs')
            ->select('id', 'ip_address')
            ->orderBy('id')
            ->chunkById(200, function ($logs) {
                foreach ($logs as $log) {
                    if (!$log->ip_address) {
                        continue;
                    }

                    $update = [];

                    $ip = $log->ip_address;

                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        $update['ip_address_v4'] = $ip;
                    } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                        $update['ip_address_v6'] = $ip;
                    }

                    if (!empty($update)) {
                        DB::table('attendance_logs')
                            ->where('id', $log->id)
                            ->update($update);
                    }
                }
            });

        DB::table('attendances')
            ->select('id', 'check_in_ip', 'check_out_ip')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $attendance) {
                    $update = [];

                    if ($attendance->check_in_ip && filter_var($attendance->check_in_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                        $update['check_in_ip_v6'] = $attendance->check_in_ip;
                    }

                    if ($attendance->check_out_ip && filter_var($attendance->check_out_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                        $update['check_out_ip_v6'] = $attendance->check_out_ip;
                    }

                    if (!empty($update)) {
                        DB::table('attendances')
                            ->where('id', $attendance->id)
                            ->update($update);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropColumn(['ip_address_v4', 'ip_address_v6']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['check_in_ip_v6', 'check_out_ip_v6']);
        });
    }
};
