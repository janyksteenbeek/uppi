<?php

namespace App\Filament\Resources;

use Str;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Select;
use App\Models\Test;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Group;
use URL;
use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\MonitorResource\Pages\ListMonitors;
use App\Filament\Resources\MonitorResource\Pages\CreateMonitor;
use App\Filament\Resources\MonitorResource\Pages\EditMonitor;
use App\Enums\Monitors\MonitorType;
use App\Enums\Monitors\ServerMetricType;
use App\Filament\Resources\MonitorResource\Pages;
use App\Filament\Resources\MonitorResource\RelationManagers\AlertsRelationManager;
use App\Filament\Resources\MonitorResource\RelationManagers\AnomaliesRelationManager;
use App\Models\Monitor;
use App\Models\Server;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class MonitorResource extends Resource
{
    protected static ?string $model = Monitor::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-heart';

    protected static string | \UnitEnum | null $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        if (! Auth::check()) {
            return null;
        }

        $count = Auth::user()->failingCount();

        if ($count === 0) {
            return null;
        }

        return $count.' failing '.Str::plural('monitor', $count);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', Auth::id());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        TextInput::make('name')
                            ->required(),
                        ToggleButtons::make('type')
                            ->inline()
                            ->grouped()
                            ->enum(MonitorType::class)
                            ->default(MonitorType::HTTP->value)
                            ->icons([
                                MonitorType::HTTP->value => 'heroicon-o-globe-alt',
                                MonitorType::TCP->value => 'heroicon-o-server-stack',
                                MonitorType::PULSE->value => 'heroicon-o-clock',
                                MonitorType::TEST->value => 'heroicon-o-beaker',
                                MonitorType::SERVER->value => 'heroicon-o-cpu-chip',
                            ])
                            ->options(MonitorType::options())
                            ->required()
                            ->live(),
                        Select::make('address')
                            ->options(fn () => Test::where('user_id', auth()->id())->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Test')
                            ->helperText('Select the test to run for this monitor')
                            ->visible(fn (Get $get) => MonitorType::resolve($get('type')) === MonitorType::TEST)
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('entrypoint_url')
                                    ->required()
                                    ->url()
                                    ->label('Entrypoint URL'),
                            ])
                            ->createOptionUsing(function (array $data): string {
                                $test = Test::create([
                                    'user_id' => auth()->id(),
                                    'name' => $data['name'],
                                    'entrypoint_url' => $data['entrypoint_url'],
                                ]);

                                return $test->id;
                            }),
                        TextInput::make('address')
                            ->required()
                            ->visible(fn (Get $get) => ! in_array($get('type'), [MonitorType::TEST->value, MonitorType::SERVER->value]))
                            ->live()
                            ->url(fn (Get $get) => MonitorType::resolve($get('type')) === MonitorType::HTTP)
                            ->numeric(fn (Get $get) => MonitorType::resolve($get('type')) === MonitorType::PULSE)
                            ->label(fn (Get $get) => MonitorType::resolve($get('type')) === MonitorType::PULSE ? 'Maximum age of check-in' : 'Address')
                            ->helperText(fn (Get $get) => MonitorType::resolve($get('type')) === MonitorType::PULSE ? 'The maximum age of the check-in minutes. If the latest check-in is older than this, the monitor will be marked as down.' : 'The address of the server to check. If the server is not reachable, the monitor will be marked as down.')
                            ->suffix(fn (Get $get) => MonitorType::resolve($get('type')) === MonitorType::PULSE ? 'minutes' : null),
                        TextInput::make('port')
                            ->numeric()
                            ->requiredIf('type', MonitorType::TCP->value)
                            ->hidden(fn (Get $get) => MonitorType::resolve($get('type')) !== MonitorType::TCP)
                            ->live(),
                        Section::make('pulse_info')
                            ->heading('Check-in')
                            ->visible(fn (Get $get, ?Monitor $record) => MonitorType::resolve($get('type')) === MonitorType::PULSE)
                            ->schema([
                                Group::make([
                                    TextInput::make('pulse_url')
                                        ->label('Pulse Check-in URL')
                                        ->disabled()
                                        ->readOnly()
                                        ->prefixIcon('heroicon-s-globe-alt')
                                        ->dehydrated(false)
                                        ->placeholder('URL will be generated after saving')
                                        ->helperText('This URL should be added to your cron job to check in with the server. The check-in will be marked as down if the endpoint doesn\'t get called within the interval.')
                                        ->formatStateUsing(fn (?Monitor $record) => $record ? URL::signedRoute('pulse.checkin', ['monitor' => $record->id]) : null)
                                        ->suffixAction(
                                            Action::make('copy_url')
                                                ->label('Copy URL')
                                                ->icon('heroicon-o-clipboard')
                                                ->action(fn () => null)
                                                ->extraAttributes(fn ($state) => [
                                                    'x-data' => '',
                                                    'data-copy' => $state,
                                                    'x-on:click' => 'navigator.clipboard.writeText($el.dataset.copy); $tooltip("URL copied")',
                                                ])
                                        ),

                                ])
                                    ->visible(fn (Get $get) => MonitorType::resolve($get('type')) === MonitorType::PULSE)
                                    ->columnSpanFull()
                                    ->hidden(fn (Get $get) => MonitorType::resolve($get('type')) !== MonitorType::PULSE),
                                TextInput::make('curl_example')
                                    ->dehydrated(false)
                                    ->label('cURL Command')
                                    ->readOnly()
                                    ->disabled()
                                    ->formatStateUsing(function (?Monitor $record, Get $get) {
                                        // If we're looking at an existing record with a token
                                        if ($record && $record->type === MonitorType::PULSE) {
                                            $token = $get('address');
                                            $tokenParam = $token ? $token : 'YOUR_TOKEN';

                                            return 'curl -X POST '.URL::signedRoute('pulse.checkin', ['monitor' => $record]);
                                        }

                                        return 'The example commands will be available after creating the monitor';
                                    })
                                    ->helperText('Add one of these commands to your cron job')
                                    ->visible(fn (Get $get) => MonitorType::resolve($get('type')) === MonitorType::PULSE)
                                    ->hidden(fn (Get $get) => MonitorType::resolve($get('type')) !== MonitorType::PULSE)
                                    ->suffixAction(
                                        Action::make('copy_curl')
                                            ->label('Copy CURL')
                                            ->icon('heroicon-o-clipboard')
                                            ->action(fn () => Notification::make()
                                                ->title('CURL copied')
                                                ->body('The CURL command has been copied to your clipboard.')
                                                ->success()
                                                ->send())
                                            ->extraAttributes(fn ($state) => [
                                                'x-data' => '',
                                                'data-copy' => $state,
                                                'x-on:click' => 'navigator.clipboard.writeText($el.dataset.copy); $tooltip("CURL copied")',
                                            ])
                                    ),
                                TextInput::make('wget_example')
                                    ->dehydrated(false)
                                    ->label('wget Command')
                                    ->readOnly()
                                    ->disabled()
                                    ->formatStateUsing(function (?Monitor $record, Get $get) {
                                        // If we're looking at an existing record with a token
                                        if ($record && $record->type === MonitorType::PULSE) {
                                            return 'wget -O /dev/null -q '.URL::signedRoute('pulse.checkin', ['monitor' => $record]);
                                        } else {
                                            return 'Generate a token first to see example commands';
                                        }
                                    })
                                    ->helperText('Add one of these commands to your cron job')
                                    ->visible(fn (Get $get) => MonitorType::resolve($get('type')) === MonitorType::PULSE)
                                    ->hidden(fn (Get $get) => MonitorType::resolve($get('type')) !== MonitorType::PULSE)
                                    ->suffixAction(
                                        Action::make('copy_wget')
                                            ->label('Copy wget')
                                            ->icon('heroicon-o-clipboard')
                                            ->action(fn () => Notification::make()
                                                ->title('wget copied')
                                                ->body('The wget command has been copied to your clipboard.')
                                                ->success()
                                                ->send())
                                            ->extraAttributes(fn ($state) => [
                                                'x-data' => '',
                                                'data-copy' => $state,
                                                'x-on:click' => 'navigator.clipboard.writeText($el.dataset.copy); $tooltip("CURL copied")',
                                            ])
                                    ),
                            ])->columns(2),

                        // Server monitoring section
                        Section::make('server_monitoring')
                            ->heading('Server Monitoring')
                            ->visible(fn (Get $get) => MonitorType::resolve($get('type')) === MonitorType::SERVER)
                            ->schema([
                                Select::make('address')
                                    ->label('Server')
                                    ->options(fn () => Server::where('user_id', auth()->id())->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('disk_mount_point', null))
                                    ->helperText('Select the server to monitor'),
                                Select::make('metric_type')
                                    ->label('Metric')
                                    ->options(ServerMetricType::options())
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, ?string $state) {
                                        if ($state) {
                                            $metricType = ServerMetricType::tryFrom($state);
                                            if ($metricType) {
                                                $set('threshold', $metricType->getDefaultThreshold());
                                            }
                                        }
                                        $set('disk_mount_point', null);
                                    })
                                    ->helperText('Select the metric to monitor'),
                                Select::make('disk_mount_point')
                                    ->label('Disk')
                                    ->options(function (Get $get) {
                                        $serverId = $get('address');
                                        if (! $serverId) {
                                            return [];
                                        }

                                        $server = Server::withoutGlobalScopes()->find($serverId);
                                        if (! $server || $server->user_id !== auth()->id()) {
                                            return [];
                                        }

                                        $latestMetric = $server->latestMetric();
                                        if (! $latestMetric) {
                                            return [];
                                        }

                                        return $latestMetric->diskMetrics
                                            ->pluck('mount_point', 'mount_point')
                                            ->toArray();
                                    })
                                    ->visible(fn (Get $get) => $get('metric_type') === ServerMetricType::DiskUsage->value)
                                    ->required(fn (Get $get) => $get('metric_type') === ServerMetricType::DiskUsage->value)
                                    ->searchable()
                                    ->helperText('Select the disk partition to monitor'),
                                Grid::make(2)
                                    ->schema([
                                        Select::make('threshold_operator')
                                            ->label('Condition')
                                            ->options([
                                                '>' => 'Greater than (>)',
                                                '>=' => 'Greater than or equal (>=)',
                                                '<' => 'Less than (<)',
                                                '<=' => 'Less than or equal (<=)',
                                            ])
                                            ->default('>')
                                            ->required()
                                            ->helperText('Alert when value is...'),
                                        TextInput::make('threshold')
                                            ->label('Threshold')
                                            ->numeric()
                                            ->required()
                                            ->step(0.1)
                                            ->suffix(fn (Get $get) => ServerMetricType::resolve($get('metric_type'))?->getUnit() ?? '%')
                                            ->helperText('The threshold value to trigger an alert'),
                                    ]),
                            ])->columns(2),

                        Toggle::make('is_enabled')
                            ->required()
                            ->default(true)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Monitor Settings')
                    ->schema([
                        TextInput::make('interval')
                            ->required()
                            ->numeric()
                            ->default(5)
                            ->step(1)
                            ->minValue(1)
                            ->helperText('Check interval in minutes'),
                        TextInput::make('consecutive_threshold')
                            ->required()
                            ->numeric()
                            ->default(state: 2)
                            ->step(1)
                            ->minValue(1)
                            ->helperText('Number of failed checks in a row needed before registering an anomaly and sending an alert'),
                        TextInput::make('user_agent')
                            ->placeholder(config('app.name'))
                            ->hidden(fn (Get $get) => MonitorType::resolve($get('type')) !== MonitorType::HTTP)
                            ->maxLength(255)
                            ->helperText('Custom User-Agent string for HTTP requests')
                            ->live(),
                        Select::make('alerts')
                            ->helperText('Alerts to send when the monitor is down')
                            ->multiple()
                            ->relationship('alerts', 'name', modifyQueryUsing: fn (Builder $query) => $query->where('user_id', auth()->id()))
                            ->preload(),
                        Toggle::make('auto_create_update')
                            ->label('Post update when anomaly is detected')
                            ->helperText('Automatically create an update once an anomaly is detected (threshold reached) on the status pages where this monitor is being shown.')
                            ->default(true)
                            ->hintAction(
                                Action::make('customize_text')
                                    ->modalHeading('Customize update text')
                                    ->modalFooter(fn () => new HtmlString('<div class="text-sm text-gray-500">Use the following variables in your update text: <code>:monitor_name</code>, <code>:monitor_address</code>, <code>:monitor_type</code></div>'))
                                    ->schema([
                                        TextInput::make('update_values.title')
                                            ->label('Update title')
                                            ->helperText('The title of the update that will be posted when an anomaly is detected.')
                                            ->default(':monitor_name is experiencing issues'),
                                        MarkdownEditor::make('update_values.content')
                                            ->label('Update content')
                                            ->helperText('The content of the update that will be posted when an anomaly is detected.')
                                            ->default("Our automated monitoring & alerting system has detected that :monitor_name is experiencing issues. Because of these issues, we've created this update to keep you informed.\n\nOur team has been notified and is investigating. We apologize for the inconvenience."),
                                    ])
                                    ->fillForm(function (?Monitor $record) {
                                        if (! $record) {
                                            return [];
                                        }

                                        return [
                                            'update_values' => [
                                                'title' => $record->update_values['title'] ?? ':monitor_name is experiencing issues',
                                                'content' => $record->update_values['content'] ?? "Our automated monitoring & alerting system has detected that :monitor_name is experiencing issues. Because of these issues, we've created this update to keep you informed.\n\nOur team has been notified and is investigating. We apologize for the inconvenience.",
                                            ],
                                        ];
                                    })
                                    ->action(function (?Monitor $record, array $data, Set $set) {
                                        if ($record) {
                                            $record->update([
                                                'update_values' => [
                                                    'title' => $data['update_values']['title'],
                                                    'content' => $data['update_values']['content'],
                                                ],
                                            ]);
                                        } else {
                                            $set('update_values', [
                                                'title' => $data['update_values']['title'],
                                                'content' => $data['update_values']['content'],
                                            ]);
                                        }
                                    })
                            ),

                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('address')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('expects')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('type')
                    ->searchable()
                    ->description(fn ($record) => ! $record->is_enabled ? 'Inactive' : $record->interval.' min, '.$record->consecutive_threshold.'x'),
                IconColumn::make('is_enabled')
                    ->boolean()
                    ->label('Enabled')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('alerts.name')
                    ->size('xs')
                    ->label('Alerts')
                    ->wrap()
                    ->wrap(),
                TextColumn::make('last_checked_at')
                    ->since()
                    ->tooltip(fn (Monitor $record) => $record->last_checked_at?->format('j F Y, g:i a'))
                    ->description(fn (Monitor $record) => ($record->last_checkin_at ? 'Checked in '.$record->last_checkin_at?->diffForHumans() : null))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'ok' => 'OK',
                        'fail' => 'Failed',
                        'pending' => 'Pending',
                    ]),
                Filter::make('last_checked_at')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('last_checked_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('last_checked_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                Action::make('toggle_enabled')
                    ->label(null)
                    ->iconButton()
                    ->tooltip(fn (Monitor $record) => $record->is_enabled ? 'Disable' : 'Enable')
                    ->action(fn (Monitor $record) => $record->update(['is_enabled' => ! $record->is_enabled]))
                    ->icon('heroicon-o-power')
                    ->color(fn (Monitor $record) => $record->is_enabled ? 'success' : 'gray'),
                EditAction::make(),
            ])
            ->emptyStateHeading('Start monitoring your website')
            ->emptyStateDescription('Set up your first monitor to check the status of your website, API or other service.')
            ->emptyStateIcon('heroicon-o-heart')
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Create a monitor')
                    ->icon('heroicon-o-plus'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('enable')
                        ->label('Enable')
                        ->action(fn ($records) => $records->each->update(['is_enabled' => true]))
                        ->deselectRecordsAfterCompletion()
                        ->icon('heroicon-o-check'),
                    BulkAction::make('disable')
                        ->label('Disable')
                        ->action(fn ($records) => $records->each->update(['is_enabled' => false]))
                        ->deselectRecordsAfterCompletion()
                        ->icon('heroicon-o-x-mark'),
                    BulkAction::make('set_alerts')
                        ->label('Set Alerts')
                        ->schema([
                            Select::make('alerts')
                                ->translateLabel()
                                ->options(fn ($record) => auth()->user()->alerts->pluck('name', 'id'))
                                ->multiple()
                                ->preload()
                                ->required(),
                        ])
                        ->action(function ($records, $data) {
                            $records->each(function ($record) use ($data) {
                                $record->alerts()->sync($data['alerts']);
                            });
                        })
                        ->icon('heroicon-o-bell'),
                    DeleteBulkAction::make(),

                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AlertsRelationManager::class,
            AnomaliesRelationManager::class,
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
