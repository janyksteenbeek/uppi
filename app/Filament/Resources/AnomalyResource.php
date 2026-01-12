<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnomalyResource\Pages;
use App\Models\Anomaly;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

class AnomalyResource extends Resource
{
    protected static ?string $model = Anomaly::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'History';

    protected static ?string $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('monitor_id')
                    ->relationship('monitor', 'name')
                    ->required()
                    ->searchable(),
                Forms\Components\DateTimePicker::make('started_at')
                    ->required(),
                Forms\Components\DateTimePicker::make('ended_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('monitor.type')
                    ->label('')
                    ->sortable(),
                Tables\Columns\TextColumn::make('monitor.name')
                    ->numeric()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('monitor.address')
                    ->description(fn ($record) => $record->monitor->port)
                    ->sortable()
                    ->label('Address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ended_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->state(fn ($record) => $record->ended_at ? $record->ended_at->diffForHumans($record->started_at, true) : null)
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderByRaw('TIMESTAMPDIFF(SECOND, started_at, COALESCE(ended_at, NOW())) ' . $direction);
                    }),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('monitor_id')
                    ->label('Monitor')
                    ->options(fn () => auth()->user()->monitors()->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('monitor_type')
                    ->label('Type')
                    ->options(\App\Enums\Monitors\MonitorType::class)
                    ->query(fn ($query, array $data) => $data['value']
                        ? $query->whereHas('monitor', fn ($q) => $q->where('type', $data['value']))
                        : $query
                    ),
                Tables\Filters\TernaryFilter::make('status')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Resolved')
                    ->falseLabel('Ongoing')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('ended_at'),
                        false: fn ($query) => $query->whereNull('ended_at'),
                    ),
                Tables\Filters\Filter::make('started_at')
                    ->form([
                        Forms\Components\DatePicker::make('started_from')
                            ->label('Started from'),
                        Forms\Components\DatePicker::make('started_until')
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
                            $indicators['started_from'] = 'From ' . \Carbon\Carbon::parse($data['started_from'])->format('M j, Y');
                        }
                        if ($data['started_until'] ?? null) {
                            $indicators['started_until'] = 'Until ' . \Carbon\Carbon::parse($data['started_until'])->format('M j, Y');
                        }
                        return $indicators;
                    }),
                Tables\Filters\Filter::make('duration')
                    ->form([
                        Forms\Components\Select::make('duration')
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
                ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAnomalies::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
