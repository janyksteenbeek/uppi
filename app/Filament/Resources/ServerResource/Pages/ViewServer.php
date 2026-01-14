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
                            ->extraAttributes(['style' => 'height: 100%;'])
                            ->visible(fn () => $server->latestMetric()?->diskMetrics?->isNotEmpty())
                            ->schema([
                                Infolists\Components\ViewEntry::make('disk_table')
                                    ->view('filament.infolists.entries.storage-table', [
                                        'server' => $server,
                                    ]),
                            ]),

                        // Network Card
                        Infolists\Components\Section::make('Network')
                            ->icon('heroicon-o-signal')
                            ->columnSpan(1)
                            ->extraAttributes(['style' => 'height: 100%;'])
                            ->visible(fn () => $server->latestMetric()?->networkMetrics?->isNotEmpty())
                            ->schema([
                                Infolists\Components\ViewEntry::make('network_table')
                                    ->view('filament.infolists.entries.network-table', [
                                        'server' => $server,
                                    ]),
                            ]),

                        // Health Check Card
                        Infolists\Components\Section::make('Health Check')
                            ->icon('heroicon-o-heart')
                            ->columnSpan(1)
                            ->extraAttributes(['style' => 'height: 100%;'])
                            ->schema([
                                Infolists\Components\ViewEntry::make('health_check')
                                    ->view('filament.infolists.entries.health-check', [
                                        'server' => $server,
                                    ]),
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
