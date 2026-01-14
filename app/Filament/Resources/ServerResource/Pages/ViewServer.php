<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Filament\Resources\ServerResource\Widgets\ServerLoadChart;
use App\Filament\Resources\ServerResource\Widgets\ServerMetricsChart;
use App\Filament\Resources\ServerResource\Widgets\ServerStatsOverview;
use App\Models\Server;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\Response;

class ViewServer extends ViewRecord
{
    protected static string $resource = ServerResource::class;

    public bool $showInactiveInterfaces = false;

    public bool $showAllDisks = false;

    public function mount(int|string $record): void
    {
        if (! Auth::user()->hasFeature('server-monitoring')) {
            abort(Response::HTTP_FORBIDDEN);
        }

        parent::mount($record);
    }

    public function getTitle(): string|Htmlable
    {
        return $this->record->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        /** @var Server $server */
        $server = $this->record;

        if (! $server->last_seen_at) {
            return null;
        }

        $parts = [];
        if ($server->hostname) {
            $parts[] = $server->hostname;
        }
        if ($server->os) {
            $parts[] = $server->os;
        }

        return implode(' • ', $parts) ?: null;
    }

    protected function getHeaderActions(): array
    {
        /** @var Server $server */
        $server = $this->record;

        return [
            Actions\EditAction::make()
                ->visible(fn () => $server->last_seen_at !== null),
            Actions\DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        /** @var Server $server */
        $server = $this->record;

        if ($server->last_seen_at === null) {
            return [];
        }

        return [
            ServerStatsOverview::class,
            ServerMetricsChart::class,
            ServerLoadChart::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'lg' => 2,
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        /** @var Server $server */
        $server = $this->record;
        $hasReceivedData = $server->last_seen_at !== null;

        return $infolist
            ->schema([
                // Onboarding section - shown when no data received yet
                Infolists\Components\Section::make('Install the monitoring agent')
                    ->description('Run this command on your server to start sending metrics to Uppi.')
                    ->icon('heroicon-o-command-line')
                    ->iconColor('primary')
                    ->visible(fn () => ! $hasReceivedData)
                    ->schema([
                        Infolists\Components\TextEntry::make('install_command')
                            ->label('')
                            ->state(fn (Server $record) => "curl -sSL https://raw.githubusercontent.com/janyksteenbeek/uppi-server-agent/main/install.sh | sudo bash -s -- {$record->id}:{$record->secret}")
                            ->copyable()
                            ->copyMessage('Install command copied!')
                            ->extraAttributes([
                                'class' => 'font-mono text-sm rounded-lg',
                                'style' => 'background-color: #1f2937; color: #4ade80; padding: 1rem;',
                            ])
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('instructions')
                            ->label('')
                            ->state(new HtmlString('
                                <div class="text-sm text-gray-500 dark:text-gray-400 space-y-2">
                                    <p><strong>Requirements:</strong> Linux server with systemd (Ubuntu, Debian, CentOS, RHEL, etc.)</p>
                                    <p><strong>What this does:</strong></p>
                                    <ul class="list-disc list-inside ml-2 space-y-1">
                                        <li>Downloads and installs the Uppi Server Agent</li>
                                        <li>Configures it with your server\'s secret key</li>
                                        <li>Sets up a systemd service to run automatically</li>
                                        <li>Starts sending CPU, memory, disk, and network metrics</li>
                                    </ul>
                                    <p class="mt-3"><strong>Waiting for first metrics...</strong> This page will show data once the agent connects.</p>
                                </div>
                            '))
                            ->html()
                            ->columnSpanFull(),
                    ]),

                // Three column grid: Storage, Network, Health Check
                Infolists\Components\Grid::make(3)
                    ->visible(fn () => $hasReceivedData)
                    ->schema([
                        // Storage Card
                        Infolists\Components\Section::make('Storage')
                            ->icon('heroicon-o-circle-stack')
                            ->columnSpan(1)
                            ->headerActions([
                                Infolists\Components\Actions\Action::make('toggle_disks')
                                    ->label(fn () => $this->showAllDisks ? 'Show less' : 'Show more')
                                    ->icon(fn () => $this->showAllDisks ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down')
                                    ->color('gray')
                                    ->action(function (): void {
                                        $this->showAllDisks = ! $this->showAllDisks;
                                        $this->dispatch('$refresh');
                                    }),
                            ])
                            ->description('Mount • Used / Total • %')
                            ->visible(fn () => $server->latestMetric()?->diskMetrics?->isNotEmpty())
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('disk_partitions_top')
                                    ->label('')
                                    ->state(function (Server $record) {
                                        $latest = $record->latestMetric();
                                        if (! $latest) {
                                            return [];
                                        }

                                        return $latest->diskMetrics
                                            ->sortByDesc(fn ($disk) => ($disk->used_bytes / max($disk->total_bytes, 1)) * 100)
                                            ->take(8)
                                            ->map(fn ($disk) => [
                                                'mount_point' => $disk->mount_point,
                                                'used_total' => "{$disk->formatted_used} / {$disk->formatted_total}",
                                                'percent' => ($disk->used_bytes / max($disk->total_bytes, 1)) * 100,
                                            ])->toArray();
                                    })
                                    ->visible(fn () => ! $this->showAllDisks)
                                    ->contained(false)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('mount_point')
                                            ->hiddenLabel()
                                            ->tooltip(fn ($state) => $state)
                                            ->extraAttributes([
                                                'style' => 'white-space: normal; word-break: break-word;',
                                            ])
                                            ->columnSpan(2),
                                        Infolists\Components\TextEntry::make('used_total')
                                            ->hiddenLabel()
                                            ->color('gray')
                                            ->alignEnd()
                                            ->columnSpan(1),
                                        Infolists\Components\TextEntry::make('percent')
                                            ->hiddenLabel()
                                            ->formatStateUsing(fn (?float $state) => number_format((float) $state, 1).'%')
                                            ->alignEnd()
                                            ->badge()
                                            ->color(fn (?float $state) => match (true) {
                                                $state > 90 => 'danger',
                                                $state > 75 => 'warning',
                                                default => 'success',
                                            })
                                            ->columnSpan(1),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull(),

                                Infolists\Components\RepeatableEntry::make('disk_partitions_all')
                                    ->label('')
                                    ->state(function (Server $record) {
                                        $latest = $record->latestMetric();
                                        if (! $latest) {
                                            return [];
                                        }

                                        return $latest->diskMetrics
                                            ->sortByDesc(fn ($disk) => ($disk->used_bytes / max($disk->total_bytes, 1)) * 100)
                                            ->map(fn ($disk) => [
                                                'mount_point' => $disk->mount_point,
                                                'used_total' => "{$disk->formatted_used} / {$disk->formatted_total}",
                                                'percent' => ($disk->used_bytes / max($disk->total_bytes, 1)) * 100,
                                            ])->toArray();
                                    })
                                    ->visible(fn () => $this->showAllDisks)
                                    ->contained(false)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('mount_point')
                                            ->hiddenLabel()
                                            ->tooltip(fn ($state) => $state)
                                            ->extraAttributes([
                                                'style' => 'white-space: normal; word-break: break-word;',
                                            ])
                                            ->columnSpan(2),
                                        Infolists\Components\TextEntry::make('used_total')
                                            ->hiddenLabel()
                                            ->color('gray')
                                            ->alignEnd()
                                            ->columnSpan(1),
                                        Infolists\Components\TextEntry::make('percent')
                                            ->hiddenLabel()
                                            ->formatStateUsing(fn (?float $state) => number_format((float) $state, 1).'%')
                                            ->alignEnd()
                                            ->badge()
                                            ->color(fn (?float $state) => match (true) {
                                                $state > 90 => 'danger',
                                                $state > 75 => 'warning',
                                                default => 'success',
                                            })
                                            ->columnSpan(1),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull(),

                                Infolists\Components\TextEntry::make('disk_partitions_note')
                                    ->label('')
                                    ->state(function (Server $record) {
                                        $latest = $record->latestMetric();
                                        if (! $latest) {
                                            return '';
                                        }

                                        if ($this->showAllDisks) {
                                            return 'Showing all partitions';
                                        }

                                        $total = $latest->diskMetrics->count();
                                        $shown = min($total, 8);
                                        $hidden = $total - $shown;

                                        return $hidden > 0 ? "{$hidden} more partition(s) hidden" : 'All partitions shown';
                                    })
                                    ->color('gray')
                                    ->columnSpanFull(),
                            ]),

                        // Network Card
                        Infolists\Components\Section::make('Network')
                            ->icon('heroicon-o-signal')
                            ->columnSpan(1)
                            ->visible(fn () => $server->latestMetric()?->networkMetrics?->isNotEmpty())
                            ->headerActions([
                                Infolists\Components\Actions\Action::make('toggle_inactive')
                                    ->label(fn () => $this->showInactiveInterfaces ? 'Hide Inactive' : 'Show Inactive')
                                    ->icon(fn () => $this->showInactiveInterfaces ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                                    ->color('gray')
                                    ->action(function (): void {
                                        $this->showInactiveInterfaces = ! $this->showInactiveInterfaces;
                                        $this->dispatch('$refresh');
                                    }),
                            ])
                            ->description('Interface • RX • TX')
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('network_interfaces_active')
                                    ->label('')
                                    ->state(function (Server $record) {
                                        $latest = $record->latestMetric();
                                        if (! $latest) {
                                            return [];
                                        }

                                        $interfaces = $latest->networkMetrics
                                            ->filter(fn ($net) => $net->rx_bytes > 0 || $net->tx_bytes > 0)
                                            ->sortByDesc(fn ($net) => $net->rx_bytes + $net->tx_bytes);

                                        return $interfaces->take(10)->map(fn ($network) => [
                                            'interface' => $network->interface_name,
                                            'rx' => $network->formatted_rx_bytes,
                                            'tx' => $network->formatted_tx_bytes,
                                            'is_active' => $network->rx_bytes > 0 || $network->tx_bytes > 0,
                                        ])->toArray();
                                    })
                                    ->visible(fn () => ! $this->showInactiveInterfaces)
                                    ->contained(false)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('interface')
                                            ->hiddenLabel()
                                            ->extraAttributes([
                                                'style' => 'white-space: normal; word-break: break-word;',
                                            ])
                                            ->columnSpan(2),
                                        Infolists\Components\TextEntry::make('rx')
                                            ->hiddenLabel()
                                            ->color('success')
                                            ->formatStateUsing(fn ($state) => '↓ '.$state)
                                            ->columnSpan(1),
                                        Infolists\Components\TextEntry::make('tx')
                                            ->hiddenLabel()
                                            ->color('info')
                                            ->formatStateUsing(fn ($state) => '↑ '.$state)
                                            ->columnSpan(1),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull(),

                                Infolists\Components\RepeatableEntry::make('network_interfaces_all')
                                    ->label('')
                                    ->state(function (Server $record) {
                                        $latest = $record->latestMetric();
                                        if (! $latest) {
                                            return [];
                                        }

                                        return $latest->networkMetrics
                                            ->sortByDesc(fn ($net) => $net->rx_bytes + $net->tx_bytes)
                                            ->take(20)
                                            ->map(fn ($network) => [
                                                'interface' => $network->interface_name,
                                                'rx' => $network->formatted_rx_bytes,
                                                'tx' => $network->formatted_tx_bytes,
                                                'is_active' => $network->rx_bytes > 0 || $network->tx_bytes > 0,
                                            ])->toArray();
                                    })
                                    ->visible(fn () => $this->showInactiveInterfaces)
                                    ->contained(false)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('interface')
                                            ->hiddenLabel()
                                            ->extraAttributes([
                                                'style' => 'white-space: normal; word-break: break-word;',
                                            ])
                                            ->color(fn ($record) => $record['is_active'] ? null : 'gray')
                                            ->columnSpan(2),
                                        Infolists\Components\TextEntry::make('rx')
                                            ->hiddenLabel()
                                            ->color('success')
                                            ->formatStateUsing(fn ($state) => '↓ '.$state)
                                            ->columnSpan(1),
                                        Infolists\Components\TextEntry::make('tx')
                                            ->hiddenLabel()
                                            ->color('info')
                                            ->formatStateUsing(fn ($state) => '↑ '.$state)
                                            ->columnSpan(1),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull(),

                                Infolists\Components\TextEntry::make('inactive_interfaces_note')
                                    ->label('')
                                    ->state(function (Server $record) {
                                        $latest = $record->latestMetric();
                                        if (! $latest) {
                                            return '';
                                        }

                                        $inactiveCount = $latest->networkMetrics
                                            ->filter(fn ($net) => $net->rx_bytes === 0 && $net->tx_bytes === 0)
                                            ->count();

                                        if ($this->showInactiveInterfaces) {
                                            return $inactiveCount > 0
                                                ? "Showing inactive interfaces ({$inactiveCount} inactive)"
                                                : 'All interfaces are active';
                                        }

                                        return $inactiveCount > 0 ? "{$inactiveCount} inactive interface(s) hidden" : 'All interfaces are active';
                                    })
                                    ->color('gray')
                                    ->columnSpanFull(),
                            ]),

                        // Health Check Card
                        Infolists\Components\Section::make('Health Check')
                            ->icon('heroicon-o-heart')
                            ->columnSpan(1)
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('health_indicators')
                                    ->label('')
                                    ->state(function (Server $record) {
                                        $latest = $record->latestMetric();
                                        if (! $latest) {
                                            return [];
                                        }

                                        $checks = [];

                                        // CPU
                                        $cpuUsage = $latest->cpu_usage;
                                        $status = 'healthy';
                                        if ($cpuUsage > 90) {
                                            $status = 'critical';
                                        } elseif ($cpuUsage > 75) {
                                            $status = 'warning';
                                        }
                                        $checks[] = [
                                            'name' => 'CPU',
                                            'value' => number_format($cpuUsage, 1).'%',
                                            'status' => $status,
                                        ];

                                        // Memory
                                        $memoryPercent = $latest->memory_total > 0
                                            ? ($latest->memory_used / $latest->memory_total) * 100
                                            : 0;
                                        $memoryStatus = 'healthy';
                                        if ($memoryPercent > 90) {
                                            $memoryStatus = 'critical';
                                        } elseif ($memoryPercent > 80) {
                                            $memoryStatus = 'warning';
                                        }
                                        $checks[] = [
                                            'name' => 'Memory',
                                            'value' => number_format($memoryPercent, 1).'%',
                                            'status' => $memoryStatus,
                                        ];

                                        // Swap
                                        $swapPercent = $latest->swap_total > 0
                                            ? ($latest->swap_used / $latest->swap_total) * 100
                                            : 0;
                                        $swapStatus = 'healthy';
                                        if ($swapPercent > 50) {
                                            $swapStatus = 'critical';
                                        } elseif ($swapPercent > 25) {
                                            $swapStatus = 'warning';
                                        }
                                        $checks[] = [
                                            'name' => 'Swap',
                                            'value' => number_format($swapPercent, 1).'%',
                                            'status' => $swapStatus,
                                        ];

                                        // Disk (worst)
                                        $worstDisk = $latest->diskMetrics->sortByDesc('usage_percent')->first();
                                        $diskPercent = $worstDisk?->usage_percent ?? 0;
                                        $diskStatus = 'healthy';
                                        if ($diskPercent > 90) {
                                            $diskStatus = 'critical';
                                        } elseif ($diskPercent > 80) {
                                            $diskStatus = 'warning';
                                        }
                                        $checks[] = [
                                            'name' => 'Disk',
                                            'value' => number_format($diskPercent, 1).'%',
                                            'status' => $diskStatus,
                                        ];

                                        // Load (1m)
                                        $load1m = $latest->load_average_1m;
                                        $loadStatus = 'healthy';
                                        if ($load1m > 10) {
                                            $loadStatus = 'critical';
                                        } elseif ($load1m > 5) {
                                            $loadStatus = 'warning';
                                        }
                                        $checks[] = [
                                            'name' => 'Load (1m)',
                                            'value' => number_format($load1m, 2),
                                            'status' => $loadStatus,
                                        ];

                                        return $checks;
                                    })
                                    ->schema([
                                        Infolists\Components\TextEntry::make('name')
                                            ->label('')
                                            ->weight('bold'),
                                        Infolists\Components\TextEntry::make('value')
                                            ->label('')
                                            ->alignEnd(),
                                        Infolists\Components\TextEntry::make('status')
                                            ->label('')
                                            ->formatStateUsing(fn (?string $state) => match ($state) {
                                                'healthy' => 'Healthy',
                                                'warning' => 'Warning',
                                                'critical' => 'Critical',
                                                null => 'Unknown',
                                                default => 'Unknown',
                                            })
                                            ->badge()
                                            ->color(fn (?string $state) => match ($state) {
                                                'healthy' => 'success',
                                                'warning' => 'warning',
                                                'critical' => 'danger',
                                                default => 'gray',
                                            }),
                                    ])
                                    ->columns(3)
                                    ->columnSpanFull(),

                                Infolists\Components\TextEntry::make('last_updated')
                                    ->label('')
                                    ->state(fn (Server $record) => $record->latestMetric()?->created_at?->diffForHumans() ?? '')
                                    ->color('gray')
                                    ->size('xs')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                // Server Details - collapsed at bottom
                Infolists\Components\Section::make('Server Details')
                    ->icon('heroicon-o-server')
                    ->visible(fn () => $hasReceivedData)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Infolists\Components\TextEntry::make('hostname')
                            ->copyable()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('ip_address')
                            ->label('Internal IP')
                            ->copyable()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('external_ip')
                            ->label('External IP')
                            ->copyable()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('os')
                            ->label('Operating System')
                            ->placeholder('—'),
                        Infolists\Components\IconEntry::make('is_active')
                            ->boolean()
                            ->label('Monitoring Active'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Added')
                            ->dateTime('j M Y, g:i a'),
                    ])
                    ->columns(3),

                // Reinstall Agent - collapsed at bottom
                Infolists\Components\Section::make('Reinstall Agent')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn () => $hasReceivedData)
                    ->collapsible()
                    ->collapsed()
                    ->description('Use this command if you need to reinstall the monitoring agent.')
                    ->schema([
                        Infolists\Components\TextEntry::make('install_command_reconnect')
                            ->label('')
                            ->state(fn (Server $record) => "curl -sSL https://raw.githubusercontent.com/janyksteenbeek/uppi-server-agent/main/install.sh | sudo bash -s -- {$record->id}:{$record->secret}")
                            ->copyable()
                            ->copyMessage('Install command copied!')
                            ->extraAttributes([
                                'class' => 'font-mono text-sm rounded-lg',
                                'style' => 'background-color: #1f2937; color: #4ade80; padding: 1rem;',
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
