<?php

namespace App\Filament\Admin\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Str;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Admin\Resources\TestResource\Pages\ListTests;
use App\Filament\Admin\Resources\TestResource\Pages\CreateTest;
use App\Filament\Admin\Resources\TestResource\Pages\EditTest;
use App\Enums\Tests\TestFlowBlockType;
use App\Enums\Tests\TestStatus;
use App\Filament\Admin\Resources\TestResource\Pages;
use App\Models\Test;
use App\Traits\WithoutUserScopes;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestResource extends Resource
{
    use WithoutUserScopes;

    protected static ?string $model = Test::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-beaker';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic information')
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('entrypoint_url')
                            ->required()
                            ->url()
                            ->label('Entrypoint URL'),
                    ])->columns(3),

                Section::make('Test flow')
                    ->schema([
                        Repeater::make('steps')
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->label('')
                            ->schema([
                                Select::make('type')
                                    ->options(TestFlowBlockType::options())
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('value', null))
                                    ->columnSpanFull(),
                                TextInput::make('value')
                                    ->label(fn (Get $get) => TestFlowBlockType::resolve($get('type'))?->getValueLabel() ?? 'Value')
                                    ->required(fn (Get $get) => TestFlowBlockType::resolve($get('type'))?->requiresValue() ?? false)
                                    ->visible(fn (Get $get) => TestFlowBlockType::resolve($get('type'))?->requiresValue() ?? false)
                                    ->columnSpan(fn (Get $get) => TestFlowBlockType::resolve($get('type'))?->requiresSelector() ? 1 : 2),
                                TextInput::make('selector')
                                    ->label(fn (Get $get) => TestFlowBlockType::resolve($get('type'))?->getSelectorLabel() ?? 'Selector')
                                    ->required(fn (Get $get) => TestFlowBlockType::resolve($get('type'))?->requiresSelector() ?? false)
                                    ->visible(fn (Get $get) => TestFlowBlockType::resolve($get('type'))?->requiresSelector() ?? false)
                                    ->columnSpan(1),
                                TextInput::make('delay_ms')
                                    ->label('Wait after')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(30000)
                                    ->step(100)
                                    ->suffix('ms')
                                    ->placeholder('0')
                                    ->helperText('Pause before next step')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->reorderable()
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(function (array $state): ?string {
                                $label = TestFlowBlockType::tryFrom($state['type'] ?? '')?->getLabel() ?? 'Step';

                                if (isset($state['value']) && $state['value']) {
                                    $label .= ': ' . Str::limit($state['value'], 30);
                                } elseif (isset($state['selector']) && $state['selector']) {
                                    $label .= ': ' . Str::limit($state['selector'], 30);
                                }

                                if (isset($state['delay_ms']) && $state['delay_ms'] > 0) {
                                    $delay = $state['delay_ms'] >= 1000
                                        ? number_format($state['delay_ms'] / 1000, 1) . 's'
                                        : $state['delay_ms'] . 'ms';
                                    $label .= " ⏱ {$delay}";
                                }

                                return $label;
                            })
                            ->addActionLabel('Add step')
                            ->defaultItems(0),
                    ]),
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
                IconColumn::make('user.feature_flags')
                    ->label('Feature')
                    ->icon(fn ($state) => in_array('run-tests', $state ?? []) ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn ($state) => in_array('run-tests', $state ?? []) ? 'success' : 'danger')
                    ->tooltip(fn ($state) => in_array('run-tests', $state ?? []) ? 'User has tests enabled' : 'User does not have tests enabled'),
                TextColumn::make('lastRun.status')
                    ->label('Status')
                    ->badge()
                    ->default('-'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('entrypoint_url')
                    ->label('URL')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn (Test $record) => $record->entrypoint_url),
                TextColumn::make('steps_count')
                    ->label('Steps')
                    ->counts('steps')
                    ->sortable(),
                TextColumn::make('monitors_count')
                    ->label('Monitors')
                    ->counts('monitors')
                    ->sortable(),
                TextColumn::make('runs_count')
                    ->label('Runs')
                    ->counts('runs')
                    ->sortable(),
                TextColumn::make('last_run_at')
                    ->label('Last run')
                    ->since()
                    ->tooltip(fn (Test $record) => $record->last_run_at?->format('j F Y, g:i a'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('last_run_status')
                    ->label('Status')
                    ->options(TestStatus::options())
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        return $query->whereHas('lastRun', fn (Builder $q) => $q->where('status', $data['value']));
                    }),
                SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Owner')
                    ->searchable()
                    ->preload(),
                Filter::make('has_monitors')
                    ->label('Has monitors')
                    ->query(fn ($query) => $query->has('monitors')),
                Filter::make('user_has_feature')
                    ->label('User has tests enabled')
                    ->query(fn ($query) => $query->whereHas('user', fn ($q) => $q->whereJsonContains('feature_flags', 'run-tests'))),
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
            'index' => ListTests::route('/'),
            'create' => CreateTest::route('/create'),
            'edit' => EditTest::route('/{record}/edit'),
        ];
    }
}
