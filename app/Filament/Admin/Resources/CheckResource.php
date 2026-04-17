<?php

namespace App\Filament\Admin\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Admin\Resources\CheckResource\Pages\ListChecks;
use App\Filament\Admin\Resources\CheckResource\Pages\CreateCheck;
use App\Filament\Admin\Resources\CheckResource\Pages\EditCheck;
use App\Filament\Admin\Resources\CheckResource\Pages;
use App\Models\Check;
use App\Traits\WithoutUserScopes;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CheckResource extends Resource
{
    use WithoutUserScopes;

    protected static ?string $model = Check::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-check-circle';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('monitor_id')
                    ->relationship('monitor', 'name')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->maxLength(255)
                    ->default('unknown'),
                TextInput::make('response_time')
                    ->numeric()
                    ->default(null),
                TextInput::make('response_code')
                    ->numeric()
                    ->default(null),
                Textarea::make('output')
                    ->columnSpanFull(),
                DateTimePicker::make('checked_at')
                    ->required(),
                Select::make('anomaly_id')
                    ->relationship('anomaly', 'id'),
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
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('response_time')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('response_code')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('checked_at')
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
                TextColumn::make('anomaly.id')
                    ->searchable(),
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
            'index' => ListChecks::route('/'),
            'create' => CreateCheck::route('/create'),
            'edit' => EditCheck::route('/{record}/edit'),
        ];
    }
}
