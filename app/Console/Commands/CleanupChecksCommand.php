<?php

namespace App\Console\Commands;

use App\Enums\Checks\Status;
use App\Models\Check;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupChecksCommand extends Command
{
    protected $signature = 'checks:cleanup 
                            {--days=7 : Number of days to keep detailed checks}
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Force delete (permanently remove, not soft delete)}';

    protected $description = 'Clean up old checks, keeping only the last successful check per day when all checks were OK';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $forceDelete = $this->option('force');

        $cutoffDate = Carbon::now()->subDays($days)->startOfDay();

        $this->info("Cleaning up checks older than {$cutoffDate->toDateString()} ({$days} days)");

        if ($dryRun) {
            $this->warn('DRY RUN - No records will be deleted');
        }

        // Get all monitor/date combinations that have checks before cutoff
        $checksToProcess = Check::query()
            ->select('monitor_id', DB::raw('DATE(checked_at) as check_date'))
            ->where('checked_at', '<', $cutoffDate)
            ->groupBy('monitor_id', 'check_date')
            ->get();

        if ($checksToProcess->isEmpty()) {
            $this->info('No old checks found to clean up.');

            return self::SUCCESS;
        }

        $this->info("Found {$checksToProcess->count()} monitor/date combinations to process");

        $totalDeleted = 0;
        $totalKept = 0;
        $bar = $this->output->createProgressBar($checksToProcess->count());
        $bar->start();

        foreach ($checksToProcess as $group) {
            $monitorId = $group->monitor_id;
            $checkDate = $group->check_date;

            // Get all checks for this monitor on this date
            $checksForDay = Check::query()
                ->where('monitor_id', $monitorId)
                ->whereDate('checked_at', $checkDate)
                ->orderBy('checked_at', 'desc')
                ->get();

            if ($checksForDay->isEmpty()) {
                $bar->advance();
                continue;
            }

            // Check if ALL checks for this day were OK
            $allOk = $checksForDay->every(fn ($check) => $check->status === Status::OK);

            if ($allOk && $checksForDay->count() > 1) {
                // Keep only the last check of the day, delete the rest
                $keepCheck = $checksForDay->first(); // Already ordered by checked_at desc
                $toDelete = $checksForDay->slice(1);

                if (! $dryRun) {
                    $query = Check::query()
                        ->where('monitor_id', $monitorId)
                        ->whereDate('checked_at', $checkDate)
                        ->where('id', '!=', $keepCheck->id);

                    if ($forceDelete) {
                        $deleted = $query->forceDelete();
                    } else {
                        $deleted = $query->delete();
                    }

                    $totalDeleted += $deleted;
                } else {
                    $totalDeleted += $toDelete->count();
                }

                $totalKept++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $deleteType = $forceDelete ? 'permanently deleted' : 'soft deleted';
        $action = $dryRun ? 'Would have deleted' : ucfirst($deleteType);

        $this->info("{$action} {$totalDeleted} checks.");
        $this->info("Kept {$totalKept} representative checks (1 per day for all-OK days).");

        return self::SUCCESS;
    }
}
