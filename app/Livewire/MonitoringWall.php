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
     * @return Collection<int, Monitor>
     */
    #[Computed(persist: true, seconds: 30)]
    public function allMonitors(): Collection
    {
        // Get monitors with only essential eager loads
        // Explicitly filter by user_id to ensure only current user's monitors are shown
        $monitors = Monitor::query()
            ->where('user_id', auth()->id())
            ->where('is_enabled', true)
            ->select(['id', 'name', 'user_id', 'is_enabled'])
            ->with([
                'anomalies' => function ($query) {
                    $query->whereNull('ended_at')
                        ->select(['id', 'monitor_id', 'started_at']);
                },
                'lastCheck' => function ($query) {
                    $query->select(['id', 'monitor_id', 'response_time', 'response_code', 'checked_at', 'status']);
                },
            ])
            ->get();

        $monitorIds = $monitors->pluck('id')->toArray();

        // Get aggregated response times in a single efficient query
        $responseTimes = [];
        if (! empty($monitorIds)) {
            $responseTimes = Check::query()
                ->whereIn('checks.monitor_id', $monitorIds)
                ->whereNotNull('checks.response_time')
                ->where('checks.checked_at', '>=', now()->subHours(2))
                ->select(['checks.monitor_id', 'checks.response_time'])
                ->orderBy('checks.checked_at', 'asc')
                ->get()
                ->groupBy('monitor_id')
                ->map(fn ($checks) => $checks->pluck('response_time')->take(30)->values()->toArray())
                ->toArray();
        }

        return $monitors->map(function (Monitor $monitor) use ($responseTimes) {
            $monitor->has_active_anomaly = $monitor->anomalies->isNotEmpty();
            $monitor->response_times = $responseTimes[$monitor->id] ?? [];
            $monitor->active_anomaly = $monitor->anomalies->first();
            $monitor->downtime_started_at = $monitor->active_anomaly?->started_at?->toIso8601String();

            return $monitor;
        });
    }

    /**
     * @return Collection<int, Monitor>
     */
    #[Computed]
    public function displayMonitors(): Collection
    {
        $monitors = $this->allMonitors;

        if (! empty($this->selectedMonitorIds)) {
            $monitors = $monitors->filter(function (Monitor $monitor) {
                return in_array($monitor->id, $this->selectedMonitorIds);
            });
        }

        // Sort: down monitors first, then by name
        return $monitors->sortBy([
            ['has_active_anomaly', 'desc'],
            ['name', 'asc'],
        ])->values();
    }

    /**
     * @return array<string, string>
     */
    #[Computed(persist: true, seconds: 60)]
    public function monitorOptions(): array
    {
        return $this->allMonitors->pluck('id', 'name')->toArray();
    }

    public function render(): View
    {
        return view('livewire.monitoring-wall')
            ->layout('livewire.monitoring-wall-layout');
    }
}
