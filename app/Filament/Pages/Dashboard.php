<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ActiveAnomalies;
use App\Filament\Widgets\AnomaliesPerMonitor;
use App\Filament\Widgets\RecentTestRuns;
use App\Filament\Widgets\ResponseTime;
use App\Filament\Widgets\StatusWidget;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static string $routePath = '/dashboard';

    private bool $isOk;

    public function __construct()
    {
        $this->isOk = auth()->user()->isOk();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Welcome back, '.auth()->user()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return auth()->user()->isOk() ? 'Everything is looking good!' : 'There are some monitors that need your attention.';
    }

    public function getColumns(): int
    {
        return 3;
    }

    public function getWidgets(): array
    {
        $baseWidgets = $this->isOk
            ? [
                StatusWidget::class,
                ResponseTime::class,
                AnomaliesPerMonitor::class,
                ActiveAnomalies::class,
            ]
            : [
                ActiveAnomalies::class,
                StatusWidget::class,
                ResponseTime::class,
                AnomaliesPerMonitor::class,
            ];

        // Add test runs widget if user has the feature
        if (auth()->user()->hasFeature('run-tests')) {
            $baseWidgets[] = RecentTestRuns::class;
        }

        return $baseWidgets;
    }
}
