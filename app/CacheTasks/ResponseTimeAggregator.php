<?php

namespace App\CacheTasks;

use RuntimeException;
use App\Models\Check;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ResponseTimeAggregator extends CacheTask
{
    public function __construct(
        private readonly ?int $interval = null,
        private readonly int $days = 7
    ) {}

    public static function getTtl(): int
    {
        return 120;
    }

    public function key(): string
    {
        return "response_time_aggregated_{$this->days}";
    }

    private function findBestInterval(): int
    {
        if ($this->userId === null) {
            throw new RuntimeException('Cache task must be scoped to a user');
        }

        $intervals = [12, 6, 3, 1];
        $checksCount = Check::where('checked_at', '>=', now()->subDays(value: 7))
            ->whereHas('monitor', function ($query) {
                $query->where('is_enabled', true)
                    ->where('user_id', $this->userId);
            })
            ->select('monitor_id', DB::raw('COUNT(checks.id) as total_checks'))
            ->groupBy('monitor_id')
            ->get();

        foreach ($intervals as $interval) {
            $requiredChecksPerMonitor = 7 * (24 / $interval);
            if ($checksCount->every(fn ($check) => $check->total_checks >= $requiredChecksPerMonitor)) {
                return $interval;
            }
        }

        return end($intervals);
    }

    public function execute(): Collection
    {
        if ($this->userId === null) {
            throw new RuntimeException('Cache task must be scoped to a user');
        }

        $interval = $this->interval ?? $this->findBestInterval();
        $start = now()->subDays($this->days)->startOfDay();

        $checks = Check::with('monitor')
            ->where('checked_at', '>=', $start)
            ->whereHas('monitor', function ($query) {
                $query->where('is_enabled', true)
                    ->where('user_id', $this->userId);
            })
            ->orderBy('monitor_id')
            ->orderBy('checked_at')
            ->get();

        return $checks->groupBy('monitor_id')->map(function ($monitorChecks) use ($interval) {
            $monitor = $monitorChecks->first()->monitor;
            $intervalData = [];

            foreach ($monitorChecks as $check) {
                $checkedAt = Carbon::parse($check->checked_at);
                $hour = (int) floor($checkedAt->hour / $interval) * $interval;
                $intervalStart = $checkedAt->copy()->hour($hour)->minute(0)->second(0);
                $label = $intervalStart->format('d-m H').'h';

                if (! isset($intervalData[$label])) {
                    $intervalData[$label] = [
                        'total_response_time' => 0,
                        'count' => 0,
                    ];
                }

                $intervalData[$label]['total_response_time'] += $check->response_time;
                $intervalData[$label]['count'] += 1;
            }

            $hours = [];
            $values = [];
            foreach ($intervalData as $label => $data) {
                $hours[] = $label;
                $values[] = round($data['total_response_time'] / $data['count'], 2);
            }

            return [
                'monitor' => $monitor,
                'hours' => $hours,
                'values' => $values,
            ];
        })->values();
    }
}
