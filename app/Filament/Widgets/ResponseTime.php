<?php

namespace App\Filament\Widgets;

use App\CacheTasks\ResponseTimeAggregator;
use App\Models\Check;
use App\Models\Monitor;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Lazy;

#[Lazy]
class ResponseTime extends ChartWidget
{
    protected ?string $heading = 'Performance';

    protected int|string|array $columnSpan = [
        'md' => 3,
        'lg' => 2,
    ];

    protected int $refreshInterval = 60;

    protected array $intervals = [12, 6, 3, 1];

    public function placeholder(): View
    {
        return view('filament.widgets.placeholder');
    }

    public function getDescription(): ?string
    {
        return 'Response times in milliseconds';
    }

    protected function getMaxHeight(): string
    {
        return '200px';
    }

    protected function getData(): array
    {
        $interval = $this->findBestInterval();
        $monitorData = $this->getAggregatedData($interval);

        return [
            'labels' => $this->getLabels($monitorData),
            'datasets' => $this->getDatasets($monitorData),
        ];
    }

    protected function findBestInterval(): int
    {
        foreach ($this->intervals as $interval) {
            $requiredChecksPerMonitor = 7 * (24 / $interval);
            $checksCount = Check::where('checked_at', '>=', now()->subDays(7))
                ->whereHas('monitor', function ($query) {
                    $query->where('is_enabled', true);
                })
                ->select('monitor_id', DB::raw('COUNT(*) as total_checks'))
                ->groupBy('monitor_id')
                ->get();
            $totalEnabledMonitors = Monitor::where('is_enabled', true)->count();
            $monitorsMeetingRequirement = $checksCount->filter(function ($monitor) use ($requiredChecksPerMonitor) {
                return $monitor->total_checks >= $requiredChecksPerMonitor;
            })->count();
            if ($monitorsMeetingRequirement === $totalEnabledMonitors) {
                return $interval;
            }
        }

        return end($this->intervals);
    }

    protected function getAggregatedData(int $interval): Collection
    {
        return (new ResponseTimeAggregator($interval))
            ->forUser(auth()->id())
            ->get();
    }

    protected function getLabels(Collection $monitorData): array
    {
        return $monitorData
            ->pluck('hours')
            ->flatten()
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    protected function getDatasets(Collection $monitorData): array
    {
        return $monitorData
            ->map(function ($data) {
                $color = self::generatePastelColorBasedOnMonitorId($data['monitor']->id);

                return [
                    'label' => $data['monitor']->name,
                    'data' => $data['values'],
                    'type' => 'line',
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'pointBackgroundColor' => $color,
                    'pointBorderColor' => $color,
                    'fill' => false,
                ];
            })
            ->toArray();
    }

    protected function getType(): string
    {
        return 'line';
    }

    public static function generatePastelColorBasedOnMonitorId(string $monitorId): string
    {
        $colors = [
            [145, 190, 230],
            [230, 145, 145],
            [145, 230, 145],
            [230, 190, 145],
            [190, 145, 230],
            [145, 230, 190],
            [230, 145, 190],
            [190, 230, 145],
            [145, 145, 230],
            [230, 230, 145],
        ];

        $hash = crc32($monitorId);
        $index = $hash % count($colors);
        [$r, $g, $b] = $colors[$index];

        return "rgba($r, $g, $b, 0.8)";
    }
}
