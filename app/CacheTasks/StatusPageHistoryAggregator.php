<?php

namespace App\CacheTasks;

use App\Jobs\RefreshCacheTaskJob;
use RuntimeException;
use App\Models\Check;
use App\Models\StatusPage;
use App\Models\StatusPageItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StatusPageHistoryAggregator extends CacheTask
{
    public function __construct(
        private readonly string $statusPageId,
        private readonly int $days = 30
    ) {}

    public function key(): string
    {
        return "status_page_history_{$this->statusPageId}_{$this->days}";
    }

    public static function getTtl(): int
    {
        return 60;
    }

    public static function refreshForUser(string $userId): void
    {
        StatusPage::where('user_id', $userId)
            ->where('is_enabled', true)
            ->select('id')
            ->get()
            ->each(function (StatusPage $page) use ($userId) {
                dispatch(new RefreshCacheTaskJob(static::class, $userId, [$page->id]))
                    ->onQueue('cache');
            });
    }

    public function execute(): Collection
    {
        if ($this->userId === null) {
            throw new RuntimeException('Cache task must be scoped to a user');
        }

        $today = now()->startOfDay();
        $start = $today->copy()->subDays($this->days);
        $end = $today->copy()->subDay()->endOfDay();

        // Get enabled items and their monitors in a single efficient query
        $query = StatusPageItem::query()
            ->where('status_page_id', $this->statusPageId)
            ->where('is_enabled', true)
            ->whereHas('statusPage', function ($query) {
                $query->where('user_id', $this->userId)
                    ->where('is_enabled', true);
            })
            ->whereHas('monitor', function ($query) {
                $query->where('is_enabled', true);
            });

        if ($this->id !== null) {
            $query->where('id', $this->id);
        }

        $items = $query->with('monitor')->get();

        if ($items->isEmpty()) {
            return collect();
        }

        $monitorIds = $items->pluck('monitor_id');

        // Get all dates with checks in a single query using a more efficient approach
        // This leverages the composite index on monitor_id, checked_at, deleted_at
        $checkDates = DB::table('checks')
            ->select(DB::raw('monitor_id, DATE(checked_at) as date'))
            ->whereIn('monitor_id', $monitorIds)
            ->whereBetween('checked_at', [$start, $end])
            ->whereNull('deleted_at')
            ->groupBy('monitor_id', DB::raw('DATE(checked_at)'))
            ->get();

        // Create a lookup array for quick access to check dates by monitor
        $checksLookup = [];
        foreach ($checkDates as $check) {
            $checksLookup[$check->monitor_id][] = $check->date;
        }

        // Get all anomalies in a single query
        $anomaliesQuery = DB::table('anomalies')
            ->select(DB::raw('monitor_id, DATE(started_at) as date'))
            ->whereIn('monitor_id', $monitorIds)
            ->whereBetween('started_at', [$start, $end]);

        $anomalyDates = $anomaliesQuery->get();

        // Create a lookup array for quick access to anomaly dates by monitor
        $anomaliesLookup = [];
        foreach ($anomalyDates as $anomaly) {
            $anomaliesLookup[$anomaly->monitor_id][] = $anomaly->date;
        }

        $result = collect();
        foreach ($items as $item) {
            $status = collect();
            $monitorChecks = collect($checksLookup[$item->monitor_id] ?? []);
            $monitorAnomalies = collect($anomaliesLookup[$item->monitor_id] ?? []);

            for ($date = $start->copy(); $date <= $end; $date->addDay()) {
                $dateString = $date->toDateString();

                if (! $monitorChecks->contains($dateString)) {
                    $status[$dateString] = null;

                    continue;
                }

                $status[$dateString] = ! $monitorAnomalies->contains($dateString);
            }

            $result[$item->id] = $status;
        }

        return $result;
    }
}
