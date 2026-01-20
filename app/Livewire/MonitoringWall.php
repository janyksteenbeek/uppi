<?php

namespace App\Livewire;

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
    #[Computed]
    public function allMonitors(): Collection
    {
        return Monitor::query()
            ->where('is_enabled', true)
            ->with([
                'anomalies' => function ($query) {
                    $query->whereNull('ended_at');
                },
                'checks' => function ($query) {
                    $query->whereNotNull('response_time')
                        ->where('checked_at', '>=', now()->subHours(6))
                        ->orderBy('checked_at', 'asc')
                        ->limit(50);
                },
                'lastCheck',
            ])
            ->get()
            ->map(function (Monitor $monitor) {
                $monitor->has_active_anomaly = $monitor->anomalies->isNotEmpty();
                $monitor->response_times = $monitor->checks->pluck('response_time')->filter()->values()->toArray();
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

    public function render(): View
    {
        return view('livewire.monitoring-wall')
            ->layout('livewire.monitoring-wall-layout');
    }
}
