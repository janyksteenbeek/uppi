<?php

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use App\Models\Anomaly;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\View\View;

class ActiveAnomalies extends BaseWidget
{
    protected static ?string $heading = null;

    protected int|string|array $columnSpan = 'full';

    public function placeholder(): View
    {
        return view('filament.widgets.placeholder');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Anomaly::query()
                    ->where('ended_at', null)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('monitor.status')
                    ->badge()
                    ->searchable()
                    ->label(''),
                TextColumn::make('monitor.type')
                    ->searchable()
                    ->label(''),
                TextColumn::make('monitor.name')
                    ->searchable(),
                TextColumn::make('monitor.address')
                    ->label('Address')
                    ->searchable()
                    ->description(fn ($record) => $record->monitor->port),
                TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('duration')
                    ->label('Duration')
                    ->state(function ($record) {
                        return $record->started_at->diffForHumans();
                    }),
            ])
            ->searchable(false)
            ->emptyStateIcon('heroicon-o-face-smile')
            ->emptyStateHeading('No anomalies found')
            ->emptyStateDescription('All systems are running smoothly')
            ->paginated(false)
            ->defaultSort('started_at', 'desc');
    }
}
