<?php

namespace App\Filament\Resources\ServerResource\Widgets;

use App\Models\Server;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Reactive;

class ServerLoadChart extends ChartWidget
{
    protected static ?string $heading = 'Load Average';

    protected static ?string $maxHeight = '250px';

    protected static ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 'full';

    #[Reactive]
    public ?string $serverId = null;

    protected function getData(): array
    {
        if (! $this->serverId) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $server = Server::withoutGlobalScopes()->find($this->serverId);

        if (! $server) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

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

        return [
            'datasets' => [
                [
                    'label' => '1 min',
                    'data' => $metrics->pluck('cpu_load_1')->toArray(),
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'transparent',
                    'tension' => 0.4,
                ],
                [
                    'label' => '5 min',
                    'data' => $metrics->pluck('cpu_load_5')->toArray(),
                    'borderColor' => 'rgb(234, 179, 8)',
                    'backgroundColor' => 'transparent',
                    'tension' => 0.4,
                ],
                [
                    'label' => '15 min',
                    'data' => $metrics->pluck('cpu_load_15')->toArray(),
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'transparent',
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
