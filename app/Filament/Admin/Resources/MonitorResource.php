<?php

namespace App\Filament\Admin\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Admin\Resources\MonitorResource\Pages\ListMonitors;
use App\Filament\Admin\Resources\MonitorResource\Pages\CreateMonitor;
use App\Filament\Admin\Resources\MonitorResource\Pages\EditMonitor;
use App\Enums\Monitors\MonitorType;
use App\Filament\Admin\Resources\MonitorResource\Pages;
use App\Models\Monitor;
use App\Traits\WithoutUserScopes;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MonitorResource extends Resource
{
    use WithoutUserScopes;

    protected static ?string $model = Monitor::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-heart';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Owner & Configuration')
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->options(MonitorType::allOptions())
                            ->required(),
                        TextInput::make('address')
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Settings')
                    ->schema([
                        TextInput::make('port')
                            ->numeric()
                            ->default(null),
                        TextInput::make('interval')
                            ->required()
                            ->numeric()
                            ->suffix('minutes')
                            ->default(1),
                        TextInput::make('consecutive_threshold')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->helperText('Number of consecutive failures before alerting'),
                        Toggle::make('is_enabled')
                            ->default(true),
                    ])->columns(4),

                Section::make('Advanced')
                    ->collapsed()
                    ->schema([
                        Textarea::make('body')
                            ->columnSpanFull(),
                        TextInput::make('expects')
                            ->maxLength(255)
                            ->helperText('Expected response text'),
                        TextInput::make('user_agent')
                            ->maxLength(255),
                        Select::make('status')
                            ->options([
                                'unknown' => 'Unknown',
                                'ok' => 'OK',
                                'fail' => 'Fail',
                            ])
                            ->default('unknown'),
                        DateTimePicker::make('last_checked_at'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => MonitorType::tryFrom($state)?->getLabel() ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'http' => 'primary',
                        'tcp' => 'warning',
                        'pulse' => 'info',
                        'test' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('address')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn (Monitor $record) => $record->address),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'ok' => 'success',
                        'fail' => 'danger',
                        default => 'gray',
                    }),
                IconColumn::make('is_enabled')
                    ->label('Enabled')
                    ->boolean(),
                TextColumn::make('interval')
                    ->suffix('m')
                    ->sortable(),
                TextColumn::make('last_checked_at')
                    ->label('Last check')
                    ->since()
                    ->tooltip(fn (Monitor $record) => $record->last_checked_at?->format('j F Y, g:i a'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options(MonitorType::allOptions()),
                SelectFilter::make('status')
                    ->options([
                        'unknown' => 'Unknown',
                        'ok' => 'OK',
                        'fail' => 'Fail',
                    ]),
                TernaryFilter::make('is_enabled')
                    ->label('Enabled'),
                SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Owner')
                    ->searchable()
                    ->preload(),
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
            'index' => ListMonitors::route('/'),
            'create' => CreateMonitor::route('/create'),
            'edit' => EditMonitor::route('/{record}/edit'),
        ];
    }
}
