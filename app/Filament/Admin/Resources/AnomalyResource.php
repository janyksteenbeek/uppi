<?php

namespace App\Filament\Admin\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Admin\Resources\AnomalyResource\Pages\ListAnomalies;
use App\Filament\Admin\Resources\AnomalyResource\Pages\CreateAnomaly;
use App\Filament\Admin\Resources\AnomalyResource\Pages\EditAnomaly;
use App\Filament\Admin\Resources\AnomalyResource\Pages;
use App\Models\Anomaly;
use App\Traits\WithoutUserScopes;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AnomalyResource extends Resource
{
    use WithoutUserScopes;

    protected static ?string $model = Anomaly::class;

    protected static string | \BackedEnum | null $navigationIcon = 'phosphor-warning-circle';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('monitor_id')
                    ->relationship('monitor', 'name')
                    ->required(),
                DateTimePicker::make('started_at')
                    ->required(),
                DateTimePicker::make('ended_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                TextColumn::make('monitor.name')
                    ->searchable(),
                TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ended_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnomalies::route('/'),
            'create' => CreateAnomaly::route('/create'),
            'edit' => EditAnomaly::route('/{record}/edit'),
        ];
    }
}
