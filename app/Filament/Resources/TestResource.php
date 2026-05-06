<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Str;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use App\Jobs\Checks\TestCheckJob;
use Filament\Notifications\Notification;
use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\TestResource\RelationManagers\RunsRelationManager;
use App\Filament\Resources\TestResource\Widgets\FeatureNotEnabledWidget;
use App\Filament\Resources\TestResource\Pages\ListTests;
use App\Filament\Resources\TestResource\Pages\CreateTest;
use App\Filament\Resources\TestResource\Pages\EditTest;
use App\Enums\Tests\TestFlowBlockType;
use App\Enums\Tests\TestStatus;
use App\Filament\Resources\TestResource\Pages;
use App\Filament\Resources\TestResource\RelationManagers;
use App\Filament\Resources\TestResource\Widgets;
use App\Models\Test;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TestResource extends Resource
{
    protected static ?string $model = Test::class;

    protected static string | \BackedEnum | null $navigationIcon = 'phosphor-flask';

    protected static string | \UnitEnum | null $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', Auth::id());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('A descriptive name for this test'),
                        TextInput::make('entrypoint_url')
                            ->required()
                            ->url()
                            ->label('Entrypoint URL')
                            ->helperText('The starting URL for the test flow'),
                    ])->columns(2),

                Section::make('Test flow')
                    ->description('Build your test flow by adding steps. The test starts by visiting the entrypoint URL, then executes each step in order.')
                    ->schema([
                        Repeater::make('steps')
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->label('')
                            ->collapsed()
                            ->schema([
                                Select::make('type')
                                    ->options(TestFlowBlockType::options())
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('value', null))
                                    ->helperText(fn (Get $get) => TestFlowBlockType::resolve($get('type'))?->getDescription())
                                    ->columnSpanFull(),
                                TextInput::make('value')
                                    ->label(fn (Get $get) => TestFlowBlockType::resolve($get('type'))?->getValueLabel() ?? 'Value')
                                    ->required(fn (Get $get) => TestFlowBlockType::resolve($get('type'))?->requiresValue() ?? false)
                                    ->visible(fn (Get $get) => TestFlowBlockType::resolve($get('type'))?->requiresValue() ?? false)
                                    ->placeholder(fn (Get $get) => match (TestFlowBlockType::resolve($get('type'))) {
                                        TestFlowBlockType::VISIT => 'https://example.com/page',
                                        TestFlowBlockType::WAIT_FOR_TEXT => 'Welcome to our site',
                                        TestFlowBlockType::TYPE => 'john@example.com',
                                        TestFlowBlockType::SELECT => 'option-value',
                                        TestFlowBlockType::PRESS => 'Submit',
                                        TestFlowBlockType::CLICK_LINK => 'Read more',
                                        default => null,
                                    })
                                    ->helperText(fn (Get $get) => match (TestFlowBlockType::resolve($get('type'))) {
                                        TestFlowBlockType::TYPE => 'The text to type into the field',
                                        TestFlowBlockType::SELECT => 'The option value (not display text)',
                                        TestFlowBlockType::PRESS => 'Text shown on the button element',
                                        TestFlowBlockType::CLICK_LINK => 'Text shown on the link (exact match)',
                                        TestFlowBlockType::WAIT_FOR_TEXT => 'Text that must appear on the page',
                                        default => null,
                                    })
                                    ->columnSpan(fn (Get $get) => TestFlowBlockType::resolve($get('type'))?->requiresSelector() ? 1 : 2),
                                TextInput::make('selector')
                                    ->label(fn (Get $get) => TestFlowBlockType::resolve($get('type'))?->getSelectorLabel() ?? 'Selector')
                                    ->required(fn (Get $get) => TestFlowBlockType::resolve($get('type'))?->requiresSelector() ?? false)
                                    ->visible(fn (Get $get) => TestFlowBlockType::resolve($get('type'))?->requiresSelector() ?? false)
                                    ->placeholder(fn (Get $get) => match (TestFlowBlockType::resolve($get('type'))) {
                                        TestFlowBlockType::TYPE => 'email or #email or [name="email"]',
                                        TestFlowBlockType::SELECT => 'country or #country or [name="country"]',
                                        TestFlowBlockType::CHECK, TestFlowBlockType::UNCHECK => 'terms or #terms or [name="accept_terms"]',
                                        TestFlowBlockType::CLICK => '#submit-btn, .nav-link',
                                        default => null,
                                    })
                                    ->helperText(fn (Get $get) => match (TestFlowBlockType::resolve($get('type'))) {
                                        TestFlowBlockType::TYPE => 'Field name attribute (e.g. "email") or CSS selector (e.g. "#email")',
                                        TestFlowBlockType::SELECT => 'Field name attribute (e.g. "country") or CSS selector (e.g. "#country")',
                                        TestFlowBlockType::CHECK, TestFlowBlockType::UNCHECK => 'Field name attribute or CSS selector',
                                        TestFlowBlockType::CLICK => 'CSS selector for any clickable element',
                                        default => null,
                                    })
                                    ->columnSpan(1),
                                TextInput::make('delay_ms')
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
                TextColumn::make('lastRun.status')
                    ->label('Status')
                    ->badge()
                    ->default('-')
                    ->description(function (Test $record) {
                        $lastRun = $record->lastRun;
                        if (! $lastRun) {
                            return null;
                        }

                        $successCount = $lastRun->runSteps()->where('status', TestStatus::SUCCESS)->count();
                        $totalCount = $lastRun->runSteps()->count();

                        return "{$successCount}/{$totalCount} steps";
                    })
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('entrypoint_url')
                    ->label('URL')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn (Test $record) => $record->entrypoint_url),
                TextColumn::make('steps_count')
                    ->label('Steps')
                    ->counts('steps')
                    ->suffix(' steps'),
                TextColumn::make('monitors_count')
                    ->label('Monitors')
                    ->counts('monitors')
                    ->suffix(' monitors'),
                TextColumn::make('last_run_at')
                    ->label('Last run')
                    ->since()
                    ->tooltip(fn (Test $record) => $record->last_run_at?->format('j F Y, g:i a'))
                    ->sortable(),
                TextColumn::make('lastRun.duration_ms')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1000, 2) . 's' : '-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
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
            ])
            ->recordActions([
                Action::make('run')
                    ->label('Run now')
                    ->icon('phosphor-play')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Run test now')
                    ->modalDescription('This will queue the test to run immediately.')
                    ->visible(fn ($record) => Auth::user()->hasFeature('run-tests') && $record->monitors()->exists())
                    ->action(function (Test $record) {
                        $monitor = $record->monitors()->first();


                        // Dispatch the test job
                        TestCheckJob::dispatch($monitor);

                        Notification::make()
                            ->title('Test queued')
                            ->body('The test has been queued to run. Results will appear shortly.')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->emptyStateHeading('Create your first test')
            ->emptyStateDescription('Set up automated browser tests to verify your website or application is working correctly. Then use them in monitors.')
            ->emptyStateIcon('phosphor-flask')
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Create a test')
                    ->icon('phosphor-plus'),
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
            RunsRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            FeatureNotEnabledWidget::class,
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
