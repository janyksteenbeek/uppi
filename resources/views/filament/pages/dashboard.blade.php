<x-filament-panels::page>
    <div wire:init="loadDashboard" class="fi-dashboard space-y-6">
        {{-- Hero status --}}
        @if ($hero)
            @include('filament.pages.dashboard.partials.hero', [
                'hero' => $hero,
                'statusDonut' => $statusDonut,
            ])
        @else
            <div class="pulse-skeleton" style="height: 180px;"></div>
        @endif

        {{-- KPI grid --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            @if ($kpis)
                @foreach ($kpis as $kpi)
                    @include('filament.pages.dashboard.partials.kpi', ['kpi' => $kpi])
                @endforeach
            @else
                @foreach (range(1, 6) as $i)
                    <div class="pulse-skeleton" style="height: 108px;"></div>
                @endforeach
            @endif
        </div>

        {{-- Performance chart --}}
        <x-filament::section>
            <x-slot name="heading">Response time</x-slot>
            <x-slot name="description">Average response time per monitor over the last 7 days</x-slot>

            <div class="min-h-[260px]">
                @if ($performance)
                    @if (count($performance['datasets'] ?? []) === 0)
                        @include('filament.pages.dashboard.partials.empty', [
                            'icon' => 'phosphor-chart-bar',
                            'title' => 'No response data yet',
                            'lede' => 'Checks will populate this chart as they run.',
                        ])
                    @else
                        @include('filament.pages.dashboard.partials.chart', [
                            'id' => 'performance',
                            'type' => 'line',
                            'data' => $performance,
                            'maxHeight' => '240px',
                            'options' => [
                                'maintainAspectRatio' => false,
                                'interaction' => ['mode' => 'index', 'intersect' => false],
                                'plugins' => [
                                    'legend' => ['display' => true, 'position' => 'bottom', 'labels' => ['boxWidth' => 12, 'boxHeight' => 12, 'padding' => 12, 'usePointStyle' => true]],
                                    'tooltip' => ['mode' => 'index', 'intersect' => false],
                                ],
                                'scales' => [
                                    'y' => ['beginAtZero' => true, 'grid' => ['drawBorder' => false]],
                                    'x' => ['grid' => ['display' => false]],
                                ],
                            ],
                        ])
                    @endif
                @else
                    <div class="pulse-skeleton" style="height: 240px;"></div>
                @endif
            </div>
        </x-filament::section>

        {{-- Incidents trend + Anomalies per monitor --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">Incidents trend</x-slot>
                <x-slot name="description">Last 14 days</x-slot>

                <div class="min-h-[220px]">
                    @if ($incidentsTrend)
                        @include('filament.pages.dashboard.partials.chart', [
                            'id' => 'incidents-trend',
                            'type' => 'bar',
                            'data' => $incidentsTrend,
                            'maxHeight' => '200px',
                            'options' => [
                                'maintainAspectRatio' => false,
                                'plugins' => [
                                    'legend' => ['display' => false],
                                    'tooltip' => ['enabled' => true],
                                ],
                                'scales' => [
                                    'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0], 'grid' => ['drawBorder' => false]],
                                    'x' => ['grid' => ['display' => false]],
                                ],
                            ],
                        ])
                    @else
                        <div class="pulse-skeleton" style="height: 200px;"></div>
                    @endif
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Top monitors with incidents</x-slot>
                <x-slot name="description">Last 30 days</x-slot>

                <div class="min-h-[220px]">
                    @if ($anomaliesPerMonitor)
                        @if (count($anomaliesPerMonitor['labels'] ?? []) === 0)
                            @include('filament.pages.dashboard.partials.empty', [
                                'icon' => 'phosphor-shield-check',
                                'title' => 'No incidents in the last 30 days',
                                'lede' => 'Keep up the good work.',
                                'tone' => 'good',
                            ])
                        @else
                            @include('filament.pages.dashboard.partials.chart', [
                                'id' => 'anomalies-per-monitor',
                                'type' => 'bar',
                                'data' => $anomaliesPerMonitor,
                                'maxHeight' => '200px',
                                'options' => [
                                    'maintainAspectRatio' => false,
                                    'indexAxis' => 'y',
                                    'plugins' => [
                                        'legend' => ['display' => false],
                                    ],
                                    'scales' => [
                                        'x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0], 'grid' => ['drawBorder' => false]],
                                        'y' => ['grid' => ['display' => false]],
                                    ],
                                ],
                            ])
                        @endif
                    @else
                        <div class="pulse-skeleton" style="height: 200px;"></div>
                    @endif
                </div>
            </x-filament::section>
        </div>

        {{-- Active anomalies --}}
        <x-filament::section>
            <x-slot name="heading">Active anomalies</x-slot>
            <x-slot name="description">Incidents that are currently ongoing</x-slot>

            <div class="min-h-[180px]">
                @if ($activeAnomalies !== null)
                    @if (count($activeAnomalies) === 0)
                        @include('filament.pages.dashboard.partials.empty', [
                            'icon' => 'phosphor-smiley',
                            'title' => 'No anomalies found',
                            'lede' => 'All systems are running smoothly.',
                            'tone' => 'good',
                        ])
                    @else
                        @include('filament.pages.dashboard.partials.anomalies-table', ['rows' => $activeAnomalies])
                    @endif
                @else
                    @include('filament.pages.dashboard.partials.table-skeleton', ['rows' => 4])
                @endif
            </div>
        </x-filament::section>

        {{-- Recent test runs (conditional) --}}
        @if ($hasTests)
            <x-filament::section>
                <x-slot name="heading">Recent test runs</x-slot>
                <x-slot name="description">Latest browser test executions</x-slot>

                <div class="min-h-[180px]">
                    @if ($recentTestRuns !== null)
                        @if (count($recentTestRuns) === 0)
                            @include('filament.pages.dashboard.partials.empty', [
                                'icon' => 'phosphor-flask',
                                'title' => 'No test runs yet',
                                'lede' => 'Run a test to see results here.',
                            ])
                        @else
                            @include('filament.pages.dashboard.partials.test-runs-table', ['rows' => $recentTestRuns])
                        @endif
                    @else
                        @include('filament.pages.dashboard.partials.table-skeleton', ['rows' => 4])
                    @endif
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
