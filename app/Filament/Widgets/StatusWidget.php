<?php

namespace App\Filament\Widgets;

use App\Models\Anomaly;
use App\Models\Monitor;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\View\View;

class StatusWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public function placeholder(): View
    {
        return view('filament.widgets.placeholder');
    }

    protected function getStats(): array
    {
        $anomalyGraph = Anomaly::where('ended_at', null)->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count')
            ->toArray();

        $monitorGraph = Monitor::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count')
            ->toArray();
        // only my own alert triggers
        $alertGraph = auth()->user()->alertTriggers()->selectRaw('DATE(alert_triggers.created_at) as date, COUNT(alert_triggers.id) as count')
            ->groupBy('date', 'user_id')
            ->orderBy('date')
            ->get()
            ->pluck('count')
            ->toArray();

        $anomalyCount = auth()->user()->anomalies()->whereNull('ended_at')->count();

        return [
            Stat::make('Need attention', $anomalyCount)->chart($anomalyGraph),
            Stat::make('Total monitors', Monitor::count())->chart($monitorGraph),
            Stat::make('Incidents last 7 days', auth()->user()->alertTriggers()->where('alert_triggers.created_at', '>=', now()->subDays(7))->count())->chart($alertGraph),

        ];
    }
}
