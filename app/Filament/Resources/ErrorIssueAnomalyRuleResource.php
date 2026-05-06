<?php

namespace App\Filament\Resources;

use App\Enums\ErrorTracking\IssueAlertCondition;
use App\Enums\ErrorTracking\IssueLevel;
use App\Filament\Resources\ErrorIssueAnomalyRuleResource\Pages;
use App\Models\Alert;
use App\Models\ErrorTracking\IssueAnomalyRule;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ErrorIssueAnomalyRuleResource extends Resource
{
    protected static ?string $model = IssueAnomalyRule::class;

    protected static string|\BackedEnum|null $navigationIcon = 'phosphor-bell-ringing';

    protected static ?string $navigationLabel = 'Alert rules';

    protected static string|\UnitEnum|null $navigationGroup = 'Error Tracking';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(120)
                            ->columnSpanFull(),
                        Select::make('project_id')
                            ->label('Project')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Toggle::make('is_enabled')
                            ->default(true)
                            ->inline(false),
                    ]),

                Section::make('Condition')
                    ->description('When should this rule trigger?')
                    ->schema([
                        ToggleButtons::make('condition_type')
                            ->options(IssueAlertCondition::class)
                            ->inline()
                            ->required()
                            ->live()
                            ->helperText(fn ($get) => IssueAlertCondition::resolve($get('condition_type'))?->getDescription())
                            ->columnSpanFull(),
                        TextInput::make('threshold_count')
                            ->label('Event count')
                            ->numeric()
                            ->minValue(1)
                            ->required(fn (Get $get) => $get('condition_type') === IssueAlertCondition::EVENT_COUNT_THRESHOLD->value)
                            ->visible(fn (Get $get) => $get('condition_type') === IssueAlertCondition::EVENT_COUNT_THRESHOLD->value),
                        TextInput::make('threshold_window_minutes')
                            ->label('Within (minutes)')
                            ->numeric()
                            ->minValue(1)
                            ->default(5)
                            ->required(fn (Get $get) => $get('condition_type') === IssueAlertCondition::EVENT_COUNT_THRESHOLD->value)
                            ->visible(fn (Get $get) => $get('condition_type') === IssueAlertCondition::EVENT_COUNT_THRESHOLD->value),
                    ]),

                Section::make('Throttle (bucketing)')
                    ->description('Avoid notification spam. After firing, this rule will not fire again for the same issue within the throttle window.')
                    ->schema([
                        TextInput::make('throttle_window_minutes')
                            ->label('Throttle window (minutes)')
                            ->numeric()
                            ->minValue(0)
                            ->default(60)
                            ->required(),
                    ]),

                Section::make('Filters (optional)')
                    ->description('Only fire for events matching all of these filters.')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Select::make('level_filter')
                            ->label('Levels')
                            ->multiple()
                            ->options(IssueLevel::class),
                        TagsInput::make('environment_filter')
                            ->label('Environments')
                            ->placeholder('production'),
                    ]),

                Section::make('Notification channels')
                    ->description('Which alerts should be sent when this rule fires? Reuses your existing alert destinations.')
                    ->schema([
                        Select::make('alerts')
                            ->relationship('alerts', 'name')
                            ->multiple()
                            ->options(fn () => Alert::query()->pluck('name', 'id'))
                            ->preload()
                            ->searchable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('project.name')
                    ->label('Project')
                    ->sortable(),
                TextColumn::make('condition_type')
                    ->badge()
                    ->label('Condition'),
                TextColumn::make('threshold_count')
                    ->label('Threshold')
                    ->state(function (IssueAnomalyRule $record): string {
                        if ($record->condition_type !== IssueAlertCondition::EVENT_COUNT_THRESHOLD) {
                            return '—';
                        }

                        return "{$record->threshold_count} / {$record->threshold_window_minutes} min";
                    }),
                TextColumn::make('throttle_window_minutes')
                    ->label('Throttle')
                    ->suffix(' min'),
                TextColumn::make('alerts_count')
                    ->label('Alerts')
                    ->counts('alerts'),
                IconColumn::make('is_enabled')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('project_id')
                    ->relationship('project', 'name'),
                SelectFilter::make('condition_type')
                    ->options(IssueAlertCondition::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListErrorIssueAnomalyRules::route('/'),
            'create' => Pages\CreateErrorIssueAnomalyRule::route('/create'),
            'edit' => Pages\EditErrorIssueAnomalyRule::route('/{record}/edit'),
        ];
    }
}
