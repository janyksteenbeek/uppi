<?php

namespace App\Filament\Admin\Resources;

use App\Enums\Monitors\MonitorType;
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
                Forms\Components\Section::make('Owner & Configuration')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->options(MonitorType::allOptions())
                            ->required(),
                        Forms\Components\TextInput::make('address')
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\TextInput::make('port')
                            ->numeric()
                            ->default(null),
                        Forms\Components\TextInput::make('interval')
                            ->required()
                            ->numeric()
                            ->suffix('minutes')
                            ->default(1),
                        Forms\Components\TextInput::make('consecutive_threshold')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->helperText('Number of consecutive failures before alerting'),
                        Forms\Components\Toggle::make('is_enabled')
                            ->default(true),
                    ])->columns(4),

                Forms\Components\Section::make('Advanced')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Textarea::make('body')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('expects')
                            ->maxLength(255)
                            ->helperText('Expected response text'),
                        Forms\Components\TextInput::make('user_agent')
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->options([
                                'unknown' => 'Unknown',
                                'ok' => 'OK',
                                'fail' => 'Fail',
                            ])
                            ->default('unknown'),
                        Forms\Components\DateTimePicker::make('last_checked_at'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => MonitorType::tryFrom($state)?->getLabel() ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'http' => 'primary',
                        'tcp' => 'warning',
                        'pulse' => 'info',
                        'test' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('address')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn (Monitor $record) => $record->address),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'ok' => 'success',
                        'fail' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_enabled')
                    ->label('Enabled')
                    ->boolean(),
                Tables\Columns\TextColumn::make('interval')
                    ->suffix('m')
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_checked_at')
                    ->label('Last check')
                    ->since()
                    ->tooltip(fn (Monitor $record) => $record->last_checked_at?->format('j F Y, g:i a'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(MonitorType::allOptions()),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'unknown' => 'Unknown',
                        'ok' => 'OK',
                        'fail' => 'Fail',
                    ]),
                Tables\Filters\TernaryFilter::make('is_enabled')
                    ->label('Enabled'),
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Owner')
                    ->searchable()
                    ->preload(),
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
