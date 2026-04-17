<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\ViewEntry;
use App\Models\Check;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Enums\Monitors\MonitorType;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Carbon\Carbon;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\AnomalyResource\Pages\ManageAnomalies;
use App\Filament\Resources\AnomalyResource\Pages\ViewAnomaly;
use App\Enums\Alerts\AlertTriggerType;
use App\Filament\Resources\AnomalyResource\Pages;
use App\Models\Anomaly;
use Filament\Forms;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnomalyResource extends Resource
{
    protected static ?string $model = Anomaly::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'History';

    protected static string | \UnitEnum | null $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('monitor_id')
                    ->relationship('monitor', 'name')
                    ->required()
                    ->searchable(),
                DateTimePicker::make('started_at')
                    ->required(),
                DateTimePicker::make('ended_at'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Overview')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->state(fn ($record) => $record->ended_at ? 'Resolved' : 'Ongoing')
                                    ->color(fn ($record) => $record->ended_at ? 'success' : 'danger'),
                                TextEntry::make('duration')
                                    ->label('Duration')
                                    ->state(function ($record) {
                                        $end = $record->ended_at ?? now();
                                        return $record->started_at->diffForHumans($end, true);
                                    })
                                    ->icon('heroicon-o-clock'),
                                TextEntry::make('checks_count')
                                    ->label('Checks')
                                    ->state(fn ($record) => $record->checks()->count())
                                    ->icon('heroicon-o-signal'),
                                TextEntry::make('alerts_count')
                                    ->label('Alerts sent')
                                    ->state(fn ($record) => $record->triggers()->count())
                                    ->icon('heroicon-o-bell'),
                            ]),
                    ]),

                Section::make('Monitor')
                    ->icon('heroicon-o-server')
                    ->collapsible()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('monitor.name')
                                    ->label('Name')
                                    ->weight(FontWeight::Bold)
                                    ->url(fn ($record) => $record->monitor ? MonitorResource::getUrl('edit', ['record' => $record->monitor]) : null),
                                TextEntry::make('monitor.type')
                                    ->label('Type')
                                    ->badge(),
                                TextEntry::make('monitor.address')
                                    ->label('Address')
                                    ->icon('heroicon-o-globe-alt'),
                                TextEntry::make('monitor.interval')
                                    ->label('Check interval')
                                    ->suffix(' seconds'),
                                TextEntry::make('monitor.consecutive_threshold')
                                    ->label('Threshold'),
                                TextEntry::make('monitor.status')
                                    ->label('Current status')
                                    ->badge()
                                    ->color(fn ($state) => match ($state?->value) {
                                        'ok' => 'success',
                                        'fail' => 'danger',
                                        default => 'gray',
                                    }),
                            ]),
                    ]),

                Section::make('Timeline')
                    ->icon('heroicon-o-calendar')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('started_at')
                                    ->label('Started')
                                    ->dateTime('M j, Y g:i:s A')
                                    ->icon('heroicon-o-arrow-right-circle')
                                    ->iconColor('danger'),
                                TextEntry::make('ended_at')
                                    ->label('Resolved')
                                    ->dateTime('M j, Y g:i:s A')
                                    ->placeholder('Still ongoing')
                                    ->icon('heroicon-o-check-circle')
                                    ->iconColor('success'),
                            ]),
                    ]),

                Section::make('Alert notifications')
                    ->icon('heroicon-o-bell-alert')
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record) => $record->triggers()->count() > 0)
                    ->schema([
                        RepeatableEntry::make('triggers')
                            ->hiddenLabel()
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('type')
                                            ->label('Type')
                                            ->badge()
                                            ->color(fn (AlertTriggerType $state) => match ($state) {
                                                AlertTriggerType::DOWN => 'danger',
                                                AlertTriggerType::RECOVERY => 'success',
                                            }),
                                        TextEntry::make('alert.name')
                                            ->label('Alert'),
                                        TextEntry::make('triggered_at')
                                            ->label('Sent at')
                                            ->dateTime('M j, Y g:i:s A'),
                                        TextEntry::make('channels_notified')
                                            ->label('Channels')
                                            ->badge()
                                            ->separator(', ')
                                            ->color('gray'),
                                    ]),
                            ])
                            ->contained(false),
                    ]),

                Section::make('Recent checks')
                    ->icon('heroicon-o-list-bullet')
                    ->collapsible()
                    ->collapsed()
                    ->description('Last 20 checks during this anomaly')
                    ->schema([
                        ViewEntry::make('checks_timeline')
                            ->view('filament.infolists.entries.checks-timeline'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('monitor')
                ->withCount(['checks', 'triggers'])
                ->addSelect([
                    'first_error' => Check::select('output')
                        ->whereColumn('anomaly_id', 'anomalies.id')
                        ->whereNotNull('output')
                        ->orderBy('checked_at')
                        ->limit(1),
                ])
            )
            ->columns([
                TextColumn::make('status')
                    ->label('')
                    ->badge()
                    ->state(fn ($record) => $record->ended_at ? 'Resolved' : 'Ongoing')
                    ->color(fn ($record) => $record->ended_at ? 'success' : 'danger')
                    ->tooltip(fn ($record) => $record->ended_at
                        ? 'Resolved ' . $record->ended_at->diffForHumans()
                        : 'Ongoing for ' . $record->started_at->diffForHumans(now(), true)
                    ),
                TextColumn::make('monitor.type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('monitor.name')
                    ->label('Monitor')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->monitor?->address),
                TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime('M j, g:i A')
                    ->sortable()
                    ->description(fn ($record) => $record->started_at->diffForHumans()),
                TextColumn::make('duration')
                    ->label('Duration')
                    ->state(function ($record) {
                        $end = $record->ended_at ?? now();
                        return $record->started_at->diffForHumans($end, true);
                    })
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderByRaw('TIMESTAMPDIFF(SECOND, started_at, COALESCE(ended_at, NOW())) ' . $direction);
                    })
                    ->color(function ($record) {
                        $minutes = $record->started_at->diffInMinutes($record->ended_at ?? now());
                        if ($minutes > 60) return 'danger';
                        if ($minutes > 15) return 'warning';
                        return 'gray';
                    }),
                TextColumn::make('checks_count')
                    ->label('Checks')
                    ->sortable()
                    ->icon('heroicon-o-signal')
                    ->color('gray'),
                TextColumn::make('triggers_count')
                    ->label('Alerts')
                    ->sortable()
                    ->icon('heroicon-o-bell')
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),
                TextColumn::make('first_error')
                    ->label('Error')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->first_error)
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                SelectFilter::make('monitor_id')
                    ->label('Monitor')
                    ->options(fn () => auth()->user()->monitors()->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('monitor_type')
                    ->label('Type')
                    ->options(MonitorType::class)
                    ->query(fn ($query, array $data) => $data['value']
                        ? $query->whereHas('monitor', fn ($q) => $q->where('type', $data['value']))
                        : $query
                    ),
                TernaryFilter::make('status')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Resolved')
                    ->falseLabel('Ongoing')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('ended_at'),
                        false: fn ($query) => $query->whereNull('ended_at'),
                    ),
                Filter::make('started_at')
                    ->schema([
                        DatePicker::make('started_from')
                            ->label('Started from'),
                        DatePicker::make('started_until')
                            ->label('Started until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['started_from'], fn ($q) => $q->whereDate('started_at', '>=', $data['started_from']))
                            ->when($data['started_until'], fn ($q) => $q->whereDate('started_at', '<=', $data['started_until']));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['started_from'] ?? null) {
                            $indicators['started_from'] = 'From ' . Carbon::parse($data['started_from'])->format('M j, Y');
                        }
                        if ($data['started_until'] ?? null) {
                            $indicators['started_until'] = 'Until ' . Carbon::parse($data['started_until'])->format('M j, Y');
                        }
                        return $indicators;
                    }),
                Filter::make('duration')
                    ->schema([
                        Select::make('duration')
                            ->label('Duration')
                            ->options([
                                '5' => 'More than 5 minutes',
                                '30' => 'More than 30 minutes',
                                '60' => 'More than 1 hour',
                                '360' => 'More than 6 hours',
                                '1440' => 'More than 24 hours',
                            ]),
                    ])
                    ->query(function ($query, array $data) {
                        if (! $data['duration']) {
                            return $query;
                        }
                        $minutes = (int) $data['duration'];
                        return $query->whereRaw(
                            'TIMESTAMPDIFF(MINUTE, started_at, COALESCE(ended_at, NOW())) >= ?',
                            [$minutes]
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['duration']) {
                            return null;
                        }
                        $labels = [
                            '5' => '> 5 min',
                            '30' => '> 30 min',
                            '60' => '> 1 hour',
                            '360' => '> 6 hours',
                            '1440' => '> 24 hours',
                        ];
                        return 'Duration: ' . ($labels[$data['duration']] ?? $data['duration']);
                    }),
                Filter::make('has_alerts')
                    ->label('With alerts')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->whereHas('triggers')),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->recordActions([
                ViewAction::make()
                    ->label('Details'),
                Action::make('go_to_monitor')
                    ->label('Monitor')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn ($record) => $record->monitor ? MonitorResource::getUrl('edit', ['record' => $record->monitor]) : null)
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Export')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function ($livewire): StreamedResponse {
                        $query = $livewire->getFilteredTableQuery();

                        return response()->streamDownload(function () use ($query) {
                            $handle = fopen('php://output', 'w');

                            // CSV header
                            fputcsv($handle, [
                                'Monitor',
                                'Type',
                                'Address',
                                'Port',
                                'Started At',
                                'Ended At',
                                'Duration (minutes)',
                                'Status',
                                'Checks',
                                'Alerts Sent',
                                'First Error',
                            ]);

                            // Stream records in chunks
                            $query->with('monitor')
                                ->withCount(['checks', 'triggers'])
                                ->chunk(500, function ($anomalies) use ($handle) {
                                foreach ($anomalies as $anomaly) {
                                    $duration = $anomaly->ended_at
                                        ? round($anomaly->started_at->diffInMinutes($anomaly->ended_at), 1)
                                        : round($anomaly->started_at->diffInMinutes(now()), 1);

                                    $firstError = $anomaly->checks()->whereNotNull('output')->first()?->output;

                                    fputcsv($handle, [
                                        $anomaly->monitor?->name ?? 'Unknown',
                                        $anomaly->monitor?->type?->value ?? 'Unknown',
                                        $anomaly->monitor?->address ?? '',
                                        $anomaly->monitor?->port ?? '',
                                        $anomaly->started_at->format('Y-m-d H:i:s'),
                                        $anomaly->ended_at?->format('Y-m-d H:i:s') ?? '',
                                        $duration,
                                        $anomaly->ended_at ? 'Resolved' : 'Ongoing',
                                        $anomaly->checks_count,
                                        $anomaly->triggers_count,
                                        $firstError ?? '',
                                    ]);
                                }
                            });

                            fclose($handle);
                        }, 'anomalies-' . now()->format('Y-m-d-His') . '.csv', [
                            'Content-Type' => 'text/csv',
                        ]);
                    }),
            ])
            ->striped()
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAnomalies::route('/'),
            'view' => ViewAnomaly::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['monitor.name', 'monitor.address'];
    }
}
