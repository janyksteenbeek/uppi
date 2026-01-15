<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Models\ServerMetric;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanupServerMetricsCommand extends Command
{
    protected $signature = 'server-metrics:cleanup
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Clean up old server metrics: keep 4/hour after 24h, keep 1/hour after 48h';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN - No records will be deleted');
        }

        $now = Carbon::now();
        $twentyFourHoursAgo = $now->copy()->subHours(24);
        $fortyEightHoursAgo = $now->copy()->subHours(48);

        $this->info('Server Metrics Cleanup');
        $this->info('======================');
        $this->info('Data < 24 hours: Keep all');
        $this->info('Data 24-48 hours: Keep 4 per hour (every ~15 min)');
        $this->info('Data > 48 hours: Keep 1 per hour');
        $this->newLine();

        $servers = Server::all();

        if ($servers->isEmpty()) {
            $this->info('No servers found.');

            return self::SUCCESS;
        }

        $totalDeleted = 0;
        $totalKept = 0;

        $bar = $this->output->createProgressBar($servers->count());
        $bar->start();

        foreach ($servers as $server) {
            $result = $this->cleanupServerMetrics($server, $twentyFourHoursAgo, $fortyEightHoursAgo, $dryRun);
            $totalDeleted += $result['deleted'];
            $totalKept += $result['kept'];
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $action = $dryRun ? 'Would delete' : 'Deleted';
        $this->info("{$action} {$totalDeleted} metrics.");
        $this->info("Kept {$totalKept} metrics.");

        return self::SUCCESS;
    }

    /**
     * @return array{deleted: int, kept: int}
     */
    private function cleanupServerMetrics(
        Server $server,
        Carbon $twentyFourHoursAgo,
        Carbon $fortyEightHoursAgo,
        bool $dryRun
    ): array {
        $deleted = 0;
        $kept = 0;

        // Process metrics between 24-48 hours old (keep 4 per hour)
        $deleted += $this->cleanupTimeRange(
            $server,
            $fortyEightHoursAgo,
            $twentyFourHoursAgo,
            4,
            $dryRun,
            $kept
        );

        // Process metrics older than 48 hours (keep 1 per hour)
        $deleted += $this->cleanupTimeRange(
            $server,
            null,
            $fortyEightHoursAgo,
            1,
            $dryRun,
            $kept
        );

        return ['deleted' => $deleted, 'kept' => $kept];
    }

    private function cleanupTimeRange(
        Server $server,
        ?Carbon $startTime,
        Carbon $endTime,
        int $keepPerHour,
        bool $dryRun,
        int &$kept
    ): int {
        $deleted = 0;

        // Get all metrics in this time range grouped by hour
        $query = ServerMetric::query()
            ->where('server_id', $server->id)
            ->where('collected_at', '<', $endTime);

        if ($startTime) {
            $query->where('collected_at', '>=', $startTime);
        }

        // Group by hour and process each hour
        $metrics = $query->orderBy('collected_at', 'desc')->get();

        if ($metrics->isEmpty()) {
            return 0;
        }

        // Group metrics by hour
        $groupedByHour = $metrics->groupBy(function ($metric) {
            return $metric->collected_at->format('Y-m-d H');
        });

        foreach ($groupedByHour as $hourKey => $hourMetrics) {
            if ($hourMetrics->count() <= $keepPerHour) {
                $kept += $hourMetrics->count();

                continue;
            }

            // Determine which metrics to keep (evenly distributed across the hour)
            $metricsToKeep = $this->selectMetricsToKeep($hourMetrics, $keepPerHour);
            $metricsToDelete = $hourMetrics->reject(fn ($m) => $metricsToKeep->contains('id', $m->id));

            $kept += $metricsToKeep->count();

            if (! $dryRun && $metricsToDelete->isNotEmpty()) {
                $deleteCount = ServerMetric::query()
                    ->whereIn('id', $metricsToDelete->pluck('id'))
                    ->delete();
                $deleted += $deleteCount;
            } else {
                $deleted += $metricsToDelete->count();
            }
        }

        return $deleted;
    }

    /**
     * Select metrics to keep, evenly distributed across the hour.
     */
    private function selectMetricsToKeep($hourMetrics, int $count): \Illuminate\Support\Collection
    {
        $sorted = $hourMetrics->sortBy('collected_at')->values();
        $total = $sorted->count();

        if ($total <= $count) {
            return $sorted;
        }

        // Select evenly spaced metrics
        $selected = collect();
        $step = ($total - 1) / max(1, $count - 1);

        for ($i = 0; $i < $count; $i++) {
            $index = (int) round($i * $step);
            $index = min($index, $total - 1);
            $selected->push($sorted[$index]);
        }

        return $selected->unique('id');
    }
}
