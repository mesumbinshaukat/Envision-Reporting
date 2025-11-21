<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\EmployeeActivityLog;
use Illuminate\Support\Carbon;

/**
 * Centralized logic for pruning and clearing attendance and activity logs.
 */
class LogRetentionService
{
    public const ATTENDANCE_RETENTION_DAYS = 30;
    public const ACTIVITY_RETENTION_DAYS = 5;

    /**
     * Remove attendance logs older than the retention window.
     */
    public function pruneAttendanceLogs(): int
    {
        $cutoff = Carbon::now()->subDays(self::ATTENDANCE_RETENTION_DAYS);

        return AttendanceLog::query()
            ->where(function ($builder) use ($cutoff) {
                $builder->where(function ($inner) use ($cutoff) {
                    $inner->whereNotNull('attempted_at')
                        ->where('attempted_at', '<', $cutoff);
                })->orWhere(function ($inner) use ($cutoff) {
                    $inner->whereNull('attempted_at')
                        ->whereNotNull('created_at')
                        ->where('created_at', '<', $cutoff);
                });
            })
            ->delete();
    }

    /**
     * Remove activity logs beyond the retention window.
     */
    public function pruneActivityLogs(?int $adminId = null): int
    {
        $cutoff = Carbon::now()->subDays(self::ACTIVITY_RETENTION_DAYS);

        $query = EmployeeActivityLog::query();

        if ($adminId !== null) {
            $query->where('admin_id', $adminId);
        }

        return $query
            ->where(function ($builder) use ($cutoff) {
                $builder->where(function ($inner) use ($cutoff) {
                    $inner->whereNotNull('occurred_at')
                        ->where('occurred_at', '<', $cutoff);
                })->orWhere(function ($inner) use ($cutoff) {
                    $inner->whereNull('occurred_at')
                        ->whereNotNull('created_at')
                        ->where('created_at', '<', $cutoff);
                });
            })
            ->delete();
    }

    /**
     * Permanently clear all attendance logs.
     */
    public function clearAttendanceLogs(): int
    {
        return AttendanceLog::query()->delete();
    }

    /**
     * Permanently clear activity logs for a specific admin.
     */
    public function clearActivityLogsForAdmin(int $adminId): int
    {
        return EmployeeActivityLog::where('admin_id', $adminId)->delete();
    }
}
