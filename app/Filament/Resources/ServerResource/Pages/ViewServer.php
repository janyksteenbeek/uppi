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
        ];
    }

    protected function getFooterWidgets(): array
    {
        /** @var Server $server */
        $server = $this->record;

        if ($server->last_seen_at === null || $server->metrics()->count() === 0) {
            return [];
        }

        return [
            ServerMetricsChart::class,
            ServerLoadChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }

    public function getWidgetData(): array
    {
        return [
            'serverId' => $this->record->id,
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
                            ->state(fn (Server $record) => "curl -sSL https://raw.githubusercontent.com/janyksteenbeek/uppi-server-agent/main/install.sh | sudo bash -s -- {$record->secret}")
                            ->copyable()
                            ->copyMessage('Install command copied!')
                            ->extraAttributes([
                                'class' => 'font-mono text-sm bg-gray-900 dark:bg-gray-800 text-green-400 p-4 rounded-lg',
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

                // Server information - shown when data has been received
                Infolists\Components\Section::make('Server Details')
                    ->visible(fn () => $hasReceivedData)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Infolists\Components\TextEntry::make('hostname')
                            ->copyable()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('ip_address')
                            ->label('IP Address')
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
                        Infolists\Components\TextEntry::make('metrics_count')
                            ->label('Total Data Points')
                            ->state(fn (Server $record) => number_format($record->metrics()->count())),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Disk Usage')
                    ->visible(fn () => $hasReceivedData && $server->latestMetric()?->diskMetrics?->isNotEmpty())
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('disk_partitions')
                            ->label('')
                            ->state(function (Server $record) {
                                $latest = $record->latestMetric();
                                if (! $latest) {
                                    return [];
                                }

                                return $latest->diskMetrics->map(fn ($disk) => [
                                    'mount_point' => $disk->mount_point,
                                    'used' => $disk->formatted_used,
                                    'total' => $disk->formatted_total,
                                    'percent' => $disk->usage_percent,
                                ])->toArray();
                            })
                            ->schema([
                                Infolists\Components\TextEntry::make('mount_point')
                                    ->label('Mount'),
                                Infolists\Components\TextEntry::make('used')
                                    ->label('Used'),
                                Infolists\Components\TextEntry::make('total')
                                    ->label('Total'),
                                Infolists\Components\TextEntry::make('percent')
                                    ->label('Usage')
                                    ->formatStateUsing(fn ($state) => number_format($state, 1).'%')
                                    ->badge()
                                    ->color(fn ($state) => match (true) {
                                        $state > 90 => 'danger',
                                        $state > 75 => 'warning',
                                        default => 'success',
                                    }),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Network Interfaces')
                    ->visible(fn () => $hasReceivedData && $server->latestMetric()?->networkMetrics?->isNotEmpty())
                    ->collapsible()
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('network_interfaces')
                            ->label('')
                            ->state(function (Server $record) {
                                $latest = $record->latestMetric();
                                if (! $latest) {
                                    return [];
                                }

                                return $latest->networkMetrics->map(fn ($network) => [
                                    'interface' => $network->interface_name,
                                    'rx' => $network->formatted_rx_bytes,
                                    'tx' => $network->formatted_tx_bytes,
                                ])->toArray();
                            })
                            ->schema([
                                Infolists\Components\TextEntry::make('interface')
                                    ->label('Interface'),
                                Infolists\Components\TextEntry::make('rx')
                                    ->label('Received'),
                                Infolists\Components\TextEntry::make('tx')
                                    ->label('Transmitted'),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                    ]),

                // Show install command even for connected servers (collapsed)
                Infolists\Components\Section::make('Reinstall Agent')
                    ->visible(fn () => $hasReceivedData)
                    ->collapsible()
                    ->collapsed()
                    ->description('Use this command if you need to reinstall the monitoring agent.')
                    ->schema([
                        Infolists\Components\TextEntry::make('install_command_reconnect')
                            ->label('')
                            ->state(fn (Server $record) => "curl -sSL https://raw.githubusercontent.com/janyksteenbeek/uppi-server-agent/main/install.sh | sudo bash -s -- {$record->secret}")
                            ->copyable()
                            ->copyMessage('Install command copied!')
                            ->extraAttributes([
                                'class' => 'font-mono text-sm bg-gray-900 dark:bg-gray-800 text-green-400 p-4 rounded-lg',
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
