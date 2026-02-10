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

        // Get the latest check per selected monitor in one query
        $latestChecks = Check::query()
            ->whereIn('checks.monitor_id', $this->selectedMonitorIds)
            ->whereRaw('checks.id = (select c2.id from checks c2 where c2.monitor_id = checks.monitor_id and c2.deleted_at is null order by c2.checked_at desc limit 1)')
            ->select(['checks.id', 'checks.monitor_id', 'checks.response_time', 'checks.response_code', 'checks.checked_at', 'checks.status'])
            ->get()
            ->keyBy('monitor_id');

        // Get last 15 response times per monitor for sparklines
        $sparklines = Check::query()
            ->whereIn('checks.monitor_id', $this->selectedMonitorIds)
            ->whereNotNull('checks.response_time')
            ->where('checks.checked_at', '>=', now()->subHour())
            ->select(['checks.monitor_id', 'checks.response_time', 'checks.checked_at'])
            ->orderBy('checks.checked_at', 'desc')
            ->limit(count($this->selectedMonitorIds) * 15)
            ->get()
            ->groupBy('monitor_id')
            ->map(fn ($checks) => $checks->sortBy('checked_at')->pluck('response_time')->values()->toArray());

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
