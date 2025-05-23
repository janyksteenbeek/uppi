<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MonitorResource\Pages;
use App\Models\Monitor;
use App\Traits\WithoutUserScopes;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MonitorResource extends Resource
{
    use WithoutUserScopes;

    protected static ?string $model = Monitor::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\TextInput::make('type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('address')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('port')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('body')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('expects')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\Toggle::make('is_enabled')
                    ->required(),
                Forms\Components\TextInput::make('interval')
                    ->required()
                    ->numeric()
                    ->default(1),
                Forms\Components\TextInput::make('consecutive_threshold')
                    ->required()
                    ->numeric()
                    ->default(1),
                Forms\Components\TextInput::make('user_agent')
                    ->maxLength(255),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255)
                    ->default('unknown'),
                Forms\Components\DateTimePicker::make('last_checked_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('port')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('expects')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_enabled')
                    ->boolean(),
                Tables\Columns\TextColumn::make('interval')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('consecutive_threshold')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_checked_at')
                    ->since()
                    ->tooltip(fn (Monitor $record) => $record->last_checked_at?->toDateTimeLocalString())
                    ->description(fn (Monitor $record) => $record->last_checkin_at?->diffForHumans())
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListMonitors::route('/'),
            'create' => Pages\CreateMonitor::route('/create'),
            'edit' => Pages\EditMonitor::route('/{record}/edit'),
        ];
    }
}
