<?php

namespace App\Filament\Resources;

use App\Enums\Tests\TestFlowBlockType;
use App\Enums\Tests\TestStatus;
use App\Filament\Resources\TestResource\Pages;
use App\Filament\Resources\TestResource\RelationManagers;
use App\Filament\Resources\TestResource\Widgets;
use App\Models\Test;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TestResource extends Resource
{
    protected static ?string $model = Test::class;

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = 'Monitoring';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', Auth::id());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('A descriptive name for this test'),
                        Forms\Components\TextInput::make('entrypoint_url')
                            ->required()
                            ->url()
                            ->label('Entrypoint URL')
                            ->helperText('The starting URL for the test flow'),
                    ])->columns(2),

                Forms\Components\Section::make('Test flow')
                    ->description('Build your test flow by adding steps. The test starts by visiting the entrypoint URL, then executes each step in order.')
                    ->schema([
                        Forms\Components\Repeater::make('steps')
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->label('')
                            ->collapsed()
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->options(TestFlowBlockType::options())
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('value', null))
                                    ->helperText(fn (Get $get) => TestFlowBlockType::tryFrom($get('type'))?->getDescription())
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('value')
                                    ->label(fn (Get $get) => TestFlowBlockType::tryFrom($get('type'))?->getValueLabel() ?? 'Value')
                                    ->required(fn (Get $get) => TestFlowBlockType::tryFrom($get('type'))?->requiresValue() ?? false)
                                    ->visible(fn (Get $get) => TestFlowBlockType::tryFrom($get('type'))?->requiresValue() ?? false)
                                    ->placeholder(fn (Get $get) => match (TestFlowBlockType::tryFrom($get('type'))) {
                                        TestFlowBlockType::VISIT => 'https://example.com/page',
                                        TestFlowBlockType::WAIT_FOR_TEXT => 'Welcome to our site',
                                        TestFlowBlockType::TYPE => 'john@example.com',
                                        TestFlowBlockType::SELECT => 'option-value',
                                        TestFlowBlockType::PRESS => 'Submit',
                                        TestFlowBlockType::CLICK_LINK => 'Read more',
                                        default => null,
                                    })
                                    ->helperText(fn (Get $get) => match (TestFlowBlockType::tryFrom($get('type'))) {
                                        TestFlowBlockType::TYPE => 'The text to type into the field',
                                        TestFlowBlockType::SELECT => 'The option value (not display text)',
                                        TestFlowBlockType::PRESS => 'Text shown on the button element',
                                        TestFlowBlockType::CLICK_LINK => 'Text shown on the link (exact match)',
                                        TestFlowBlockType::WAIT_FOR_TEXT => 'Text that must appear on the page',
                                        default => null,
                                    })
                                    ->columnSpan(fn (Get $get) => TestFlowBlockType::tryFrom($get('type'))?->requiresSelector() ? 1 : 2),
                                Forms\Components\TextInput::make('selector')
                                    ->label(fn (Get $get) => TestFlowBlockType::tryFrom($get('type'))?->getSelectorLabel() ?? 'Selector')
                                    ->required(fn (Get $get) => TestFlowBlockType::tryFrom($get('type'))?->requiresSelector() ?? false)
                                    ->visible(fn (Get $get) => TestFlowBlockType::tryFrom($get('type'))?->requiresSelector() ?? false)
                                    ->placeholder(fn (Get $get) => match (TestFlowBlockType::tryFrom($get('type'))) {
                                        TestFlowBlockType::TYPE => 'email or #email or [name="email"]',
                                        TestFlowBlockType::SELECT => 'country or #country or [name="country"]',
                                        TestFlowBlockType::CHECK, TestFlowBlockType::UNCHECK => 'terms or #terms or [name="accept_terms"]',
                                        TestFlowBlockType::CLICK => '#submit-btn, .nav-link',
                                        default => null,
                                    })
                                    ->helperText(fn (Get $get) => match (TestFlowBlockType::tryFrom($get('type'))) {
                                        TestFlowBlockType::TYPE => 'Field name attribute (e.g. "email") or CSS selector (e.g. "#email")',
                                        TestFlowBlockType::SELECT => 'Field name attribute (e.g. "country") or CSS selector (e.g. "#country")',
                                        TestFlowBlockType::CHECK, TestFlowBlockType::UNCHECK => 'Field name attribute or CSS selector',
                                        TestFlowBlockType::CLICK => 'CSS selector for any clickable element',
                                        default => null,
                                    })
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('delay_ms')
                                    ->label('Wait after')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(30000)
                                    ->step(100)
                                    ->suffix('ms')
                                    ->placeholder('0')
                                    ->helperText('Pause before the next step (e.g. 500 = 0.5s, 1000 = 1s)')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->reorderable()
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(function (array $state): ?string {
                                $label = TestFlowBlockType::tryFrom($state['type'] ?? '')?->getLabel() ?? 'Step';

                                if (isset($state['value']) && $state['value']) {
                                    $label .= ': ' . \Str::limit($state['value'], 30);
                                } elseif (isset($state['selector']) && $state['selector']) {
                                    $label .= ': ' . \Str::limit($state['selector'], 30);
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
                Tables\Columns\TextColumn::make('lastRun.status')
                    ->label('Status')
                    ->badge()
                    ->default('-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('entrypoint_url')
                    ->label('URL')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn (Test $record) => $record->entrypoint_url),
                Tables\Columns\TextColumn::make('steps_count')
                    ->label('Steps')
                    ->counts('steps')
                    ->suffix(' steps'),
                Tables\Columns\TextColumn::make('monitors_count')
                    ->label('Monitors')
                    ->counts('monitors')
                    ->suffix(' monitors'),
                Tables\Columns\TextColumn::make('last_run_at')
                    ->label('Last run')
                    ->since()
                    ->tooltip(fn (Test $record) => $record->last_run_at?->format('j F Y, g:i a'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('lastRun.duration_ms')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1000, 2) . 's' : '-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('last_run_status')
                    ->label('Status')
                    ->options(TestStatus::options())
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        return $query->whereHas('lastRun', fn (Builder $q) => $q->where('status', $data['value']));
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('run')
                    ->label('Run now')
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Run test now')
                    ->modalDescription('This will queue the test to run immediately.')
                    ->visible(fn () => Auth::user()->hasFeature('run-tests'))
                    ->action(function (Test $record) {
                        // TODO: Dispatch the test job
                        \Filament\Notifications\Notification::make()
                            ->title('Test queued')
                            ->body('The test has been queued to run.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->emptyStateHeading('Create your first test')
            ->emptyStateDescription('Set up automated browser tests to verify your website or application is working correctly. Then use them in monitors.')
            ->emptyStateIcon('heroicon-o-beaker')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create a test')
                    ->icon('heroicon-o-plus'),
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
            RelationManagers\RunsRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\FeatureNotEnabledWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTests::route('/'),
            'create' => Pages\CreateTest::route('/create'),
            'edit' => Pages\EditTest::route('/{record}/edit'),
        ];
    }
}
