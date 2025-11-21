<?php

namespace App\Console\Commands;

use App\Services\LogRetentionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PruneLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune aged attendance and activity logs based on retention policies.';

    /**
     * Execute the console command.
     */
    public function handle(LogRetentionService $retentionService): int
    {
        $attendanceDeleted = $retentionService->pruneAttendanceLogs();
        $activityDeleted = $retentionService->pruneActivityLogs();

        $this->info(sprintf(
            'Pruned %d attendance logs and %d activity logs older than their retention windows.',
            $attendanceDeleted,
            $activityDeleted
        ));

        Log::info('Scheduled log pruning command executed.', [
            'attendance_deleted' => $attendanceDeleted,
            'activity_deleted' => $activityDeleted,
        ]);

        return self::SUCCESS;
    }
}
