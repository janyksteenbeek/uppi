<?php

namespace App\Livewire;

use App\Models\Check;
use App\Models\Monitor;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MonitoringWall extends Component
{
    /**
     * @var array<string>
     */
    public array $selectedMonitorIds = [];

    public function mount(): void
    {
        // Selected monitors will be loaded from localStorage via Alpine
    }

    public function updateSelectedMonitors(array $ids): void
    {
        $this->selectedMonitorIds = $ids;
    }

    /**
     * Lightweight list for the settings panel only - no heavy relations.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function monitorOptions(): array
    {
        return Monitor::query()
            ->where('user_id', auth()->id())
            ->where('is_enabled', true)
            ->orderBy('name')
            ->pluck('id', 'name')
            ->toArray();
    }

    /**
     * Only load full data for the monitors that are actually selected.
     *
     * @return Collection<int, Monitor>
     */
    #[Computed]
    public function displayMonitors(): Collection
    {
        if (empty($this->selectedMonitorIds)) {
            return collect();
        }

        $monitors = Monitor::query()
            ->where('user_id', auth()->id())
            ->where('is_enabled', true)
            ->whereIn('id', $this->selectedMonitorIds)
            ->select(['id', 'name', 'user_id', 'is_enabled', 'last_checked_at'])
            ->with([
                'anomalies' => function ($query) {
                    $query->whereNull('ended_at')
                        ->select(['id', 'monitor_id', 'started_at'])
                        ->limit(1);
                },
            ])
            ->get();

        // Simple per-monitor lookups - each hits index directly with limit 1
        $latestChecks = collect();
        $sparklines = collect();

        foreach ($this->selectedMonitorIds as $monitorId) {
            $latestCheck = Check::query()
                ->where('monitor_id', $monitorId)
                ->orderByDesc('checked_at')
                ->select(['id', 'monitor_id', 'response_time', 'response_code', 'checked_at', 'status'])
                ->first();

            if ($latestCheck) {
                $latestChecks->put($monitorId, $latestCheck);
            }

            $times = Check::query()
                ->where('monitor_id', $monitorId)
                ->whereNotNull('response_time')
                ->where('checked_at', '>=', now()->subHour())
                ->orderByDesc('checked_at')
                ->limit(15)
                ->pluck('response_time')
                ->reverse()
                ->values()
                ->toArray();

            if (! empty($times)) {
                $sparklines->put($monitorId, $times);
            }
        }

        return $monitors->map(function (Monitor $monitor) use ($latestChecks, $sparklines) {
            $monitor->has_active_anomaly = $monitor->anomalies->isNotEmpty();
            $monitor->active_anomaly = $monitor->anomalies->first();
            $monitor->downtime_started_at = $monitor->active_anomaly?->started_at?->toIso8601String();
            $monitor->latest_check = $latestChecks->get($monitor->id);
            $monitor->response_times = $sparklines->get($monitor->id, collect())->toArray();

            return $monitor;
        })->sortBy([
            ['has_active_anomaly', 'desc'],
            ['name', 'asc'],
        ])->values();
    }

    public function render(): View
    {
        return view('livewire.monitoring-wall')
            ->layout('livewire.monitoring-wall-layout');
    }
}
