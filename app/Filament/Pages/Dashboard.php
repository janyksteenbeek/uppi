<?php

namespace App\Filament\Pages;

use App\CacheTasks\ResponseTimeAggregator;
use App\Enums\Checks\Status;
use App\Enums\Tests\TestStatus;
use App\Filament\Resources\AnomalyResource;
use App\Filament\Resources\MonitorResource;
use App\Filament\Resources\TestResource;
use App\Models\Anomaly;
use App\Models\Check;
use App\Models\Monitor;
use App\Models\Server;
use App\Models\StatusPage;
use App\Models\Test;
use App\Models\TestRun;
use App\Support\ChartTheme;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;

class Dashboard extends Page
{
    protected static string $routePath = '/dashboard';

    protected string $view = 'filament.pages.dashboard';

    protected static ?int $navigationSort = -2;

    public bool $loaded = false;

    public bool $hasTests = false;

    public bool $hasServers = false;

    public ?array $hero = null;

    public ?array $statusDonut = null;

    public ?array $kpis = null;

    public ?array $performance = null;

    public ?array $incidentsTrend = null;

    public ?array $anomaliesPerMonitor = null;

    public ?array $activeAnomalies = null;

    public ?array $recentTestRuns = null;

    public function mount(): void
    {
        $user = auth()->user();
        $this->hasTests = (bool) $user?->hasFeature('run-tests');
        $this->hasServers = (bool) $user?->hasFeature('server-monitoring');
    }

    public static function getNavigationLabel(): string
    {
        return 'Dashboard';
    }

    public static function getNavigationIcon(): string
    {
        return 'phosphor-house';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Welcome back, '.auth()->user()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    public function loadDashboard(): void
    {
        $this->hero = $this->buildHero();
        $this->statusDonut = $this->buildStatusDonut();
        $this->kpis = $this->buildKpis();
        $this->performance = $this->buildPerformance();
        $this->incidentsTrend = $this->buildIncidentsTrend();
        $this->anomaliesPerMonitor = $this->buildAnomaliesPerMonitor();
        $this->activeAnomalies = $this->buildActiveAnomalies();

        if ($this->hasTests) {
            $this->recentTestRuns = $this->buildRecentTestRuns();
        }

        $this->loaded = true;
    }

    protected function buildHero(): array
    {
        $counts = Monitor::query()
            ->where('is_enabled', true)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $up = (int) ($counts[Status::OK->value] ?? 0);
        $down = (int) ($counts[Status::FAIL->value] ?? 0);
        $unknown = (int) ($counts[Status::UNKNOWN->value] ?? 0);
        $total = $up + $down + $unknown;

        $disabled = Monitor::query()->where('is_enabled', false)->count();

        $activeIncidents = auth()->user()->anomalies()->whereNull('ended_at')->count();
        $checked = $up + $down;

        if ($total === 0) {
            $state = 'empty';
        } elseif ($down > 0) {
            $state = 'down';
        } elseif ($activeIncidents > 0) {
            $state = 'incidents';
        } elseif ($up === 0 && $unknown > 0) {
            $state = 'pending';
        } else {
            $state = 'ok';
        }

        return [
            'total' => $total,
            'up' => $up,
            'down' => $down,
            'unknown' => $unknown,
            'disabled' => $disabled,
            'active_incidents' => $activeIncidents,
            'health_percentage' => $checked > 0 ? round(($up / $checked) * 100, 1) : null,
            'state' => $state,
            'is_ok' => $state === 'ok',
        ];
    }

    protected function buildStatusDonut(): array
    {
        $up = $this->hero['up'] ?? 0;
        $down = $this->hero['down'] ?? 0;
        $unknown = $this->hero['unknown'] ?? 0;

        return [
            'labels' => ['Up', 'Down', 'Unknown'],
            'datasets' => [
                [
                    'data' => [$up, $down, $unknown],
                    'backgroundColor' => [
                        'rgba(31, 138, 91, 0.92)',   // pulse green
                        'rgba(229, 57, 46, 0.92)',   // pulse red
                        'rgba(138, 138, 147, 0.55)', // pulse muted
                    ],
                    'borderColor' => [
                        '#FBFAF7',
                        '#FBFAF7',
                        '#FBFAF7',
                    ],
                    'borderWidth' => 2,
                    'hoverOffset' => 6,
                ],
            ],
        ];
    }

    protected function buildKpis(): array
    {
        $user = auth()->user();
        $kpis = [];

        // 7-day uptime %
        $checks7d = Check::query()
            ->whereHas('monitor', fn ($q) => $q->where('user_id', $user->id)->where('is_enabled', true))
            ->where('checked_at', '>=', now()->subDays(7))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $okChecks = (int) ($checks7d[Status::OK->value] ?? 0);
        $totalChecks = array_sum($checks7d);
        $uptime = $totalChecks > 0 ? round(($okChecks / $totalChecks) * 100, 2) : 100;
        $uptimeColor = $uptime >= 99.5 ? 'success' : ($uptime >= 95 ? 'warning' : 'danger');

        $kpis[] = [
            'label' => 'Uptime (7d)',
            'value' => number_format($uptime, 2).'%',
            'icon' => 'phosphor-shield-check',
            'color' => $uptimeColor,
            'hint' => $totalChecks > 0 ? number_format($totalChecks).' checks' : 'No checks yet',
        ];

        // Avg response time (24h)
        $avgResponse = Check::query()
            ->whereHas('monitor', fn ($q) => $q->where('user_id', $user->id)->where('is_enabled', true))
            ->where('checked_at', '>=', now()->subDay())
            ->where('status', Status::OK)
            ->avg('response_time');

        $avgResponse = $avgResponse ? (int) round($avgResponse) : null;
        $rtColor = $avgResponse === null ? 'gray' : ($avgResponse < 500 ? 'success' : ($avgResponse < 1500 ? 'warning' : 'danger'));

        $kpis[] = [
            'label' => 'Avg response (24h)',
            'value' => $avgResponse !== null ? $avgResponse.' ms' : '—',
            'icon' => 'phosphor-lightning',
            'color' => $rtColor,
            'hint' => 'across successful checks',
        ];

        // Incidents last 7 days
        $incidents7d = $user->alertTriggers()
            ->where('alert_triggers.created_at', '>=', now()->subDays(7))
            ->count();

        $kpis[] = [
            'label' => 'Incidents (7d)',
            'value' => (string) $incidents7d,
            'icon' => 'phosphor-bell-ringing',
            'color' => $incidents7d === 0 ? 'success' : ($incidents7d < 5 ? 'warning' : 'danger'),
            'hint' => 'alert triggers',
        ];

        // Status pages
        $pagesTotal = StatusPage::query()->count();
        $pagesOk = StatusPage::query()->where('is_enabled', true)->get()->filter(fn ($p) => $p->isOk())->count();
        $pagesEnabled = StatusPage::query()->where('is_enabled', true)->count();

        $kpis[] = [
            'label' => 'Status pages',
            'value' => (string) $pagesTotal,
            'icon' => 'phosphor-chart-line',
            'color' => $pagesEnabled === 0 ? 'gray' : ($pagesOk === $pagesEnabled ? 'success' : 'danger'),
            'hint' => $pagesEnabled === 0 ? 'None enabled' : "{$pagesOk}/{$pagesEnabled} healthy",
        ];

        // Alerts configured
        $alerts = $user->alerts()->count();
        $alertsEnabled = $user->alerts()->where('is_enabled', true)->count();

        $kpis[] = [
            'label' => 'Alerts',
            'value' => (string) $alerts,
            'icon' => 'phosphor-megaphone',
            'color' => $alerts === 0 ? 'warning' : 'primary',
            'hint' => $alerts === 0 ? 'Configure alerts' : "{$alertsEnabled} active",
        ];

        // Servers (only if feature enabled)
        if ($this->hasServers) {
            $serversTotal = Server::query()->count();
            $serversOnline = Server::query()->get()->filter(fn ($s) => $s->isOnline())->count();

            $kpis[] = [
                'label' => 'Servers',
                'value' => $serversTotal > 0 ? "{$serversOnline}/{$serversTotal}" : '0',
                'icon' => 'phosphor-cpu',
                'color' => $serversTotal === 0 ? 'gray' : ($serversOnline === $serversTotal ? 'success' : 'danger'),
                'hint' => $serversTotal === 0 ? 'No servers connected' : ($serversOnline === $serversTotal ? 'All online' : ($serversTotal - $serversOnline).' offline'),
            ];
        }

        // Tests pass rate (only if feature enabled)
        if ($this->hasTests) {
            $testsTotal = Test::query()->count();
            $recentRuns = TestRun::query()
                ->whereHas('test', fn ($q) => $q->where('user_id', $user->id))
                ->where('started_at', '>=', now()->subDays(7))
                ->get();

            $passed = $recentRuns->where('status', TestStatus::SUCCESS)->count();
            $passRate = $recentRuns->count() > 0 ? round(($passed / $recentRuns->count()) * 100) : null;

            $kpis[] = [
                'label' => 'Test pass rate (7d)',
                'value' => $passRate !== null ? $passRate.'%' : '—',
                'icon' => 'phosphor-flask',
                'color' => $passRate === null ? 'gray' : ($passRate >= 95 ? 'success' : ($passRate >= 80 ? 'warning' : 'danger')),
                'hint' => $testsTotal === 0 ? 'No tests yet' : ($recentRuns->count() === 0 ? 'No runs in 7d' : $passed.'/'.$recentRuns->count().' passed'),
            ];
        }

        return $kpis;
    }

    protected function buildPerformance(): array
    {
        $interval = $this->findBestPerformanceInterval();
        $monitorData = (new ResponseTimeAggregator($interval))
            ->forUser(auth()->id())
            ->get();

        $labels = $monitorData
            ->pluck('hours')
            ->flatten()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $datasets = $monitorData->map(function ($data) {
            $color = self::pastelColorForMonitor($data['monitor']->id);

            return [
                'label' => $data['monitor']->name,
                'data' => $data['values'],
                'type' => 'line',
                'backgroundColor' => $color,
                'borderColor' => $color,
                'pointBackgroundColor' => $color,
                'pointBorderColor' => $color,
                'pointRadius' => 2,
                'pointHoverRadius' => 5,
                'tension' => 0.35,
                'borderWidth' => 2,
                'fill' => false,
            ];
        })->values()->toArray();

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    protected function findBestPerformanceInterval(): int
    {
        $intervals = [12, 6, 3, 1];
        $checksCount = Check::query()
            ->where('checked_at', '>=', now()->subDays(7))
            ->whereHas('monitor', fn ($q) => $q->where('is_enabled', true))
            ->select('monitor_id', DB::raw('COUNT(*) as total_checks'))
            ->groupBy('monitor_id')
            ->get();

        $totalEnabled = Monitor::query()->where('is_enabled', true)->count();

        foreach ($intervals as $interval) {
            $required = 7 * (24 / $interval);
            $meeting = $checksCount->filter(fn ($m) => $m->total_checks >= $required)->count();
            if ($meeting === $totalEnabled) {
                return $interval;
            }
        }

        return end($intervals);
    }

    protected function buildIncidentsTrend(): array
    {
        $start = now()->subDays(13)->startOfDay();

        $rows = Anomaly::query()
            ->where('started_at', '>=', $start)
            ->selectRaw('DATE(started_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $labels = [];
        $values = [];
        $colors = [];

        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $count = (int) ($rows[$date] ?? 0);
            $labels[] = now()->subDays($i)->format('M j');
            $values[] = $count;
            // Pulse palette: muted line for clean days, signature red for incidents
            $colors[] = $count === 0
                ? 'rgba(232, 230, 225, 0.85)'   // pulse line
                : ($count < 3 ? 'rgba(214, 164, 55, 0.9)' : 'rgba(229, 57, 46, 0.92)');
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Incidents',
                    'data' => $values,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderRadius' => 6,
                    'borderSkipped' => false,
                ],
            ],
        ];
    }

    protected function buildAnomaliesPerMonitor(): array
    {
        $anomalies = Anomaly::query()
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('monitor_id, COUNT(*) as count')
            ->groupBy('monitor_id')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'monitor_id')
            ->toArray();

        $labels = [];
        $values = [];
        $colors = [];

        $monitors = Monitor::query()->whereIn('id', array_keys($anomalies))->get()->keyBy('id');

        foreach ($anomalies as $monitorId => $count) {
            $monitor = $monitors->get($monitorId);
            if (! $monitor) {
                continue;
            }
            $labels[] = $monitor->name;
            $values[] = $count;
            $colors[] = self::pastelColorForMonitor($monitor->id);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Incidents',
                    'data' => $values,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderRadius' => 6,
                    'borderSkipped' => false,
                ],
            ],
        ];
    }

    protected function buildActiveAnomalies(): array
    {
        return Anomaly::query()
            ->with('monitor')
            ->whereNull('ended_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function (Anomaly $anomaly) {
                $monitor = $anomaly->monitor;

                return [
                    'id' => $anomaly->id,
                    'monitor_status_label' => $monitor?->status?->label(),
                    'monitor_status_color' => $monitor?->status?->getColor() ?? 'gray',
                    'monitor_type' => $monitor?->type?->value,
                    'monitor_name' => $monitor?->name,
                    'monitor_address' => $monitor?->address,
                    'monitor_port' => $monitor?->port,
                    'monitor_url' => $monitor ? MonitorResource::getUrl('edit', ['record' => $monitor]) : null,
                    'view_url' => AnomalyResource::getUrl('view', ['record' => $anomaly]),
                    'started_at' => $anomaly->started_at?->format('M j, Y g:i a'),
                    'duration' => $anomaly->started_at?->diffForHumans(null, true),
                ];
            })
            ->toArray();
    }

    protected function buildRecentTestRuns(): array
    {
        return TestRun::query()
            ->whereHas('test', fn ($q) => $q->where('user_id', auth()->id()))
            ->with(['test', 'runSteps'])
            ->orderByDesc('started_at')
            ->limit(10)
            ->get()
            ->map(function (TestRun $run) {
                $success = $run->runSteps->where('status', TestStatus::SUCCESS)->count();
                $total = $run->runSteps->count();

                return [
                    'id' => $run->id,
                    'status_label' => $run->status?->getLabel(),
                    'status_color' => $run->status?->getColor() ?? 'gray',
                    'test_name' => $run->test?->name,
                    'test_url' => $run->test ? TestResource::getUrl('edit', ['record' => $run->test]) : null,
                    'steps' => "{$success}/{$total}",
                    'duration' => $run->duration_ms ? number_format($run->duration_ms / 1000, 2).'s' : '-',
                    'started_at' => $run->started_at?->format('M j, Y g:i:s a'),
                    'started_at_human' => $run->started_at?->diffForHumans(),
                ];
            })
            ->toArray();
    }

    public static function pastelColorForMonitor(string $monitorId): string
    {
        return ChartTheme::colorForKeyRgba($monitorId, 0.85);
    }
}
