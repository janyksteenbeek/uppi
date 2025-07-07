<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Models\Server;
use App\Models\ServerMetric;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewServer extends ViewRecord
{
    protected static string $resource = ServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('filter_metrics')
                ->label('Filter Metrics')
                ->icon('heroicon-o-funnel')
                ->form([
                    Forms\Components\DateTimePicker::make('start_date')
                        ->label('Start Date')
                        ->default(now()->subDays(7)),
                    Forms\Components\DateTimePicker::make('end_date')
                        ->label('End Date')
                        ->default(now()),
                ])
                ->action(function (array $data) {
                    // This would typically update the page state to filter metrics
                    session(['metrics_filter' => $data]);
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Server Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('name'),
                        Infolists\Components\TextEntry::make('hostname'),
                        Infolists\Components\TextEntry::make('ip_address')
                            ->label('IP Address'),
                        Infolists\Components\TextEntry::make('os')
                            ->label('Operating System'),
                        Infolists\Components\IconEntry::make('is_active')
                            ->boolean()
                            ->label('Active'),
                        Infolists\Components\TextEntry::make('last_seen_at')
                            ->label('Last Seen')
                            ->since(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Latest Metrics')
                    ->schema([
                        Infolists\Components\TextEntry::make('latest_cpu_usage')
                            ->label('CPU Usage')
                            ->formatStateUsing(function (Server $record) {
                                $latest = $record->latestMetric();
                                return $latest ? number_format($latest->cpu_usage, 1) . '%' : 'No data';
                            }),
                        Infolists\Components\TextEntry::make('latest_memory_usage')
                            ->label('Memory Usage')
                            ->formatStateUsing(function (Server $record) {
                                $latest = $record->latestMetric();
                                return $latest ? number_format($latest->memory_usage_percent, 1) . '%' : 'No data';
                            }),
                        Infolists\Components\TextEntry::make('latest_swap_usage')
                            ->label('Swap Usage')
                            ->formatStateUsing(function (Server $record) {
                                $latest = $record->latestMetric();
                                return $latest ? number_format($latest->swap_usage_percent, 1) . '%' : 'No data';
                            }),
                        Infolists\Components\TextEntry::make('latest_load_average')
                            ->label('Load Average (1/5/15 min)')
                            ->formatStateUsing(function (Server $record) {
                                $latest = $record->latestMetric();
                                if (!$latest) return 'No data';
                                return sprintf('%.2f / %.2f / %.2f', 
                                    $latest->cpu_load_1, 
                                    $latest->cpu_load_5, 
                                    $latest->cpu_load_15
                                );
                            }),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Disk Usage')
                    ->schema([
                        Infolists\Components\TextEntry::make('disk_metrics')
                            ->label('')
                            ->formatStateUsing(function (Server $record) {
                                $latest = $record->latestMetric();
                                if (!$latest || !$latest->disk_metrics) {
                                    return 'No disk data available';
                                }
                                
                                $output = '';
                                foreach ($latest->disk_metrics as $disk) {
                                    $used = $disk['used'] ?? 0;
                                    $total = $disk['total'] ?? 1;
                                    $percentage = $total > 0 ? round(($used / $total) * 100, 1) : 0;
                                    $output .= sprintf("%s: %s / %s (%s%%)\n", 
                                        $disk['mount'] ?? 'Unknown',
                                        $this->formatBytes($used),
                                        $this->formatBytes($total),
                                        $percentage
                                    );
                                }
                                return trim($output);
                            })
                            ->html()
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Recent Metrics')
                    ->schema([
                        Infolists\Components\TextEntry::make('metrics_summary')
                            ->label('Metrics Summary (Last 24 Hours)')
                            ->formatStateUsing(function (Server $record) {
                                $metrics = $record->metrics()
                                    ->where('collected_at', '>=', now()->subDays(1))
                                    ->count();
                                
                                $avgCpu = $record->metrics()
                                    ->where('collected_at', '>=', now()->subDays(1))
                                    ->avg('cpu_usage');
                                
                                $avgMemory = $record->metrics()
                                    ->where('collected_at', '>=', now()->subDays(1))
                                    ->avg('memory_usage_percent');

                                return sprintf(
                                    "%d data points\nAvg CPU: %s%%\nAvg Memory: %s%%",
                                    $metrics,
                                    $avgCpu ? number_format($avgCpu, 1) : 'N/A',
                                    $avgMemory ? number_format($avgMemory, 1) : 'N/A'
                                );
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor(log($bytes, 1024));
        
        return sprintf('%.2f %s', $bytes / pow(1024, $factor), $units[$factor]);
    }
}