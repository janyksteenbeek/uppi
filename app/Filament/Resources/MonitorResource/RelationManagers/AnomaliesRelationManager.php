<?php

namespace App\Filament\Resources\MonitorResource\RelationManagers;

use App\Filament\Resources\AnomalyResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AnomaliesRelationManager extends RelationManager
{
    protected static string $relationship = 'anomalies';

    protected static ?string $title = 'Alert History';

    protected static ?string $icon = 'heroicon-o-clock';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withCount(['checks', 'triggers'])
                ->addSelect([
                    'first_error' => \App\Models\Check::select('output')
                        ->whereColumn('anomaly_id', 'anomalies.id')
                        ->whereNotNull('output')
                        ->orderBy('checked_at')
                        ->limit(1),
                ])
            )
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->label('')
                    ->badge()
                    ->state(fn ($record) => $record->ended_at ? 'Resolved' : 'Ongoing')
                    ->color(fn ($record) => $record->ended_at ? 'success' : 'danger')
                    ->tooltip(fn ($record) => $record->ended_at
                        ? 'Resolved '.$record->ended_at->diffForHumans()
                        : 'Ongoing for '.$record->started_at->diffForHumans(now(), true)
                    ),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime('M j, g:i A')
                    ->sortable()
                    ->description(fn ($record) => $record->started_at->diffForHumans()),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->state(function ($record) {
                        $end = $record->ended_at ?? now();

                        return $record->started_at->diffForHumans($end, true);
                    })
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderByRaw('TIMESTAMPDIFF(SECOND, started_at, COALESCE(ended_at, NOW())) '.$direction);
                    })
                    ->color(function ($record) {
                        $minutes = $record->started_at->diffInMinutes($record->ended_at ?? now());
                        if ($minutes > 60) {
                            return 'danger';
                        }
                        if ($minutes > 15) {
                            return 'warning';
                        }

                        return 'gray';
                    }),
                Tables\Columns\TextColumn::make('checks_count')
                    ->label('Checks')
                    ->sortable()
                    ->icon('heroicon-o-signal')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('triggers_count')
                    ->label('Alerts')
                    ->sortable()
                    ->icon('heroicon-o-bell')
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),
                Tables\Columns\TextColumn::make('first_error')
                    ->label('Error')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->first_error)
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('status')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Resolved')
                    ->falseLabel('Ongoing')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('ended_at'),
                        false: fn ($query) => $query->whereNull('ended_at'),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => AnomalyResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('No anomalies recorded')
            ->emptyStateDescription('When this monitor experiences downtime, anomalies will appear here.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->striped()
            ->poll('30s');
    }
}
