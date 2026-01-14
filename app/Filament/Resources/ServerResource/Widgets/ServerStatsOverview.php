<?php

namespace App\Filament\Resources\ServerResource\Widgets;

use App\Models\Server;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class ServerStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '10s';

    protected int|string|array $columnSpan = 'full';

    public ?Model $record = null;

    protected function getStats(): array
    {
        if (! $this->record) {
            return [];
        }

        /** @var Server $server */
        $server = $this->record;

        $latest = $server->latestMetric();

        if (! $latest) {
            return [
                Stat::make('Status', 'Waiting for data')
                    ->description('No metrics received yet')
                    ->color('warning')
                    ->icon('heroicon-o-clock'),
            ];
        }

        $recentMetrics = $server->metrics()
            ->where('collected_at', '>=', now()->subHour())
            ->orderBy('collected_at')
            ->pluck('cpu_usage')
            ->toArray();

        $recentMemory = $server->metrics()
            ->where('collected_at', '>=', now()->subHour())
            ->orderBy('collected_at')
            ->pluck('memory_usage_percent')
            ->toArray();

        $cpuColor = match (true) {
            $latest->cpu_usage > 90 => 'danger',
            $latest->cpu_usage > 70 => 'warning',
            default => 'success',
        };

        $memoryColor = match (true) {
            $latest->memory_usage_percent > 90 => 'danger',
            $latest->memory_usage_percent > 70 => 'warning',
            default => 'success',
        };

        $swapColor = match (true) {
            $latest->swap_usage_percent > 80 => 'danger',
            $latest->swap_usage_percent > 50 => 'warning',
            default => 'gray',
        };

        return [
            Stat::make('CPU Usage', number_format($latest->cpu_usage, 1).'%')
                ->description('Load: '.number_format($latest->cpu_load_1, 2))
                ->chart($recentMetrics)
                ->color($cpuColor),

            Stat::make('Memory Usage', number_format($latest->memory_usage_percent, 1).'%')
                ->description($latest->formatted_memory_used.' / '.$latest->formatted_memory_total)
                ->chart($recentMemory)
                ->color($memoryColor),

            Stat::make('Swap Usage', number_format($latest->swap_usage_percent, 1).'%')
                ->description($latest->formatted_swap_used.' / '.$latest->formatted_swap_total)
                ->color($swapColor),

            Stat::make('Last Seen', $server->last_seen_at?->diffForHumans() ?? 'Never')
                ->description($server->isOnline() ? 'Online' : 'Offline')
                ->color($server->isOnline() ? 'success' : 'danger'),
        ];
    }
}
