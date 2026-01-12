<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnomalyResource\Pages;
use App\Models\Anomaly;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
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
                //
            ])
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
