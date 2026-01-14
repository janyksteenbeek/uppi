<?php

namespace App\Filament\Resources\ServerResource\Widgets;

use App\Models\Server;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ServerMetricsChart extends ChartWidget
{
    protected static ?string $heading = 'CPU & Memory';

    protected static ?string $maxHeight = '200px';

    protected static ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = [
        'default' => 2,
        'lg' => 1,
    ];

    public ?Model $record = null;

    protected function getData(): array
    {
        if (! $this->record) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        /** @var Server $server */
        $server = $this->record;

        $metrics = $server->metrics()
            ->where('collected_at', '>=', now()->subHours(24))
            ->orderBy('collected_at')
            ->get();

        if ($metrics->isEmpty()) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $labels = $metrics->map(fn ($m) => Carbon::parse($m->collected_at)->format('H:i'))->toArray();
        $cpuData = $metrics->pluck('cpu_usage')->toArray();
        $memoryData = $metrics->pluck('memory_usage_percent')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'CPU Usage (%)',
                    'data' => $cpuData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Memory Usage (%)',
                    'data' => $memoryData,
                    'borderColor' => 'rgb(168, 85, 247)',
                    'backgroundColor' => 'rgba(168, 85, 247, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'min' => 0,
                    'max' => 100,
                    'ticks' => [
                        'callback' => '(value) => value + "%"',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
        ];
    }
}
