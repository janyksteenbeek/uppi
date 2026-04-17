<?php

namespace App\Filament\Widgets;

use App\Models\Anomaly;
use App\Models\Monitor;
use Filament\Widgets\ChartWidget;
use Illuminate\View\View;
use Livewire\Attributes\Lazy;

#[Lazy]
class AnomaliesPerMonitor extends ChartWidget
{
    protected ?string $heading = 'Anomalies';

    protected int|string|array $columnSpan = [
        'md' => 3,
        'lg' => 1,
    ];

    public function getDescription(): ?string
    {
        return 'Anomalies per monitor in the last 30 days';
    }

    public function placeholder(): View
    {
        return view('filament.widgets.placeholder');
    }

    protected function getMaxHeight(): ?string
    {
        return '180px';
    }

    protected function getData(): array
    {
        $anomalies = Anomaly::query()
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('monitor_id, COUNT(*) as count')
            ->groupBy('monitor_id')
            ->get()
            ->pluck('count', 'monitor_id')
            ->toArray();

        $monitors = Monitor::all();
        $labels = [];
        $datasets = [];
        $colors = [];

        foreach ($monitors as $monitor) {
            $count = $anomalies[$monitor->id] ?? 0;
            if ($count > 0) {
                $labels[] = $monitor->name;
                $datasets[] = $count;
                $colors[] = ResponseTime::generatePastelColorBasedOnMonitorId($monitor->id);
            }
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Incidents',
                    'data' => $datasets,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'chart' => [
                'height' => 100,
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
