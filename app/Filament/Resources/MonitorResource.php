<?php

namespace App\Filament\Resources;

use App\Enums\Monitors\MonitorType;
use App\Filament\Resources\MonitorResource\Pages;
use App\Filament\Resources\MonitorResource\RelationManagers\AlertsRelationManager;
use App\Models\Monitor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
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

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Monitoring';

    public static function getNavigationBadge(): ?string
    {
        if (! Auth::check()) {
            return null;
        }

        $count = Auth::user()->failingCount();

        if ($count === 0) {
            return null;
        }

        return $count.' failing '.\Str::plural('monitor', $count);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', Auth::id());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required(),
                        Forms\Components\ToggleButtons::make('type')
                            ->inline()
                            ->grouped()
                            ->enum(MonitorType::class)
                            ->default(MonitorType::HTTP->value)
                            ->icons([
                                MonitorType::HTTP->value => 'heroicon-o-globe-alt',
                                MonitorType::TCP->value => 'heroicon-o-server-stack',
                                MonitorType::PULSE->value => 'heroicon-o-clock',
                                MonitorType::TEST->value => 'heroicon-o-beaker',
                            ])
                            ->options(MonitorType::options())
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('address')
                            ->options(fn () => \App\Models\Test::where('user_id', auth()->id())->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Test')
                            ->helperText('Select the test to run for this monitor')
                            ->visible(fn (Get $get) => $get('type') === MonitorType::TEST->value)
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('entrypoint_url')
                                    ->required()
                                    ->url()
                                    ->label('Entrypoint URL'),
                            ])
                            ->createOptionUsing(function (array $data): string {
                                $test = \App\Models\Test::create([
                                    'user_id' => auth()->id(),
                                    'name' => $data['name'],
                                    'entrypoint_url' => $data['entrypoint_url'],
                                ]);
                                return $test->id;
                            }),
                        Forms\Components\TextInput::make('address')
                            ->required()
                            ->visible(fn (Get $get) => $get('type') !== MonitorType::TEST->value)
                            ->live()
                            ->url(fn (Get $get) => $get('type') === MonitorType::HTTP->value)
                            ->numeric(fn (Get $get) => $get('type') === MonitorType::PULSE->value)
                            ->label(fn (Get $get) => $get('type') === MonitorType::PULSE->value ? 'Maximum age of check-in' : 'Address')
                            ->helperText(fn (Get $get) => $get('type') === MonitorType::PULSE->value ? 'The maximum age of the check-in minutes. If the latest check-in is older than this, the monitor will be marked as down.' : 'The address of the server to check. If the server is not reachable, the monitor will be marked as down.')
                            ->suffix(fn (Get $get) => $get('type') === MonitorType::PULSE->value ? 'minutes' : null),
                        Forms\Components\TextInput::make('port')
                            ->numeric()
                            ->requiredIf('type', MonitorType::TCP->value)
                            ->hidden(fn (Get $get) => $get('type') !== MonitorType::TCP->value)
                            ->live(),
                        Forms\Components\Section::make('pulse_info')
                            ->heading('Check-in')
                            ->visible(fn (Get $get, ?Monitor $record) => $get('type') === MonitorType::PULSE->value)
                            ->schema([
                                Forms\Components\Group::make([
                                    Forms\Components\TextInput::make('pulse_url')
                                        ->label('Pulse Check-in URL')
                                        ->disabled()
                                        ->readOnly()
                                        ->prefixIcon('heroicon-s-globe-alt')
                                        ->dehydrated(false)
                                        ->placeholder('URL will be generated after saving')
                                        ->helperText('This URL should be added to your cron job to check in with the server. The check-in will be marked as down if the endpoint doesn\'t get called within the interval.')
                                        ->formatStateUsing(fn (?Monitor $record) => $record ? \URL::signedRoute('pulse.checkin', ['monitor' => $record->id]) : null)
                                        ->suffixAction(
                                            Forms\Components\Actions\Action::make('copy_url')
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
                                    ->visible(fn (Get $get) => $get('type') === MonitorType::PULSE->value)
                                    ->columnSpanFull()
                                    ->hidden(fn (Get $get) => $get('type') !== MonitorType::PULSE->value),
                                Forms\Components\TextInput::make('curl_example')
                                    ->dehydrated(false)
                                    ->label('cURL Command')
                                    ->readOnly()
                                    ->disabled()
                                    ->formatStateUsing(function (?Monitor $record, Get $get) {
                                        // If we're looking at an existing record with a token
                                        if ($record && $record->type === MonitorType::PULSE) {
                                            $token = $get('address');
                                            $tokenParam = $token ? $token : 'YOUR_TOKEN';

                                            return 'curl -X POST '.\URL::signedRoute('pulse.checkin', ['monitor' => $record]);
                                        }

                                        return 'The example commands will be available after creating the monitor';
                                    })
                                    ->helperText('Add one of these commands to your cron job')
                                    ->visible(fn (Get $get) => $get('type') === MonitorType::PULSE->value)
                                    ->hidden(fn (Get $get) => $get('type') !== MonitorType::PULSE->value)
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('copy_curl')
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
                                Forms\Components\TextInput::make('wget_example')
                                    ->dehydrated(false)
                                    ->label('wget Command')
                                    ->readOnly()
                                    ->disabled()
                                    ->formatStateUsing(function (?Monitor $record, Get $get) {
                                        // If we're looking at an existing record with a token
                                        if ($record && $record->type === MonitorType::PULSE) {
                                            return 'wget -O /dev/null -q '.\URL::signedRoute('pulse.checkin', ['monitor' => $record]);
                                        } else {
                                            return 'Generate a token first to see example commands';
                                        }
                                    })
                                    ->helperText('Add one of these commands to your cron job')
                                    ->visible(fn (Get $get) => $get('type') === MonitorType::PULSE->value)
                                    ->hidden(fn (Get $get) => $get('type') !== MonitorType::PULSE->value)
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('copy_wget')
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

                        Forms\Components\Toggle::make('is_enabled')
                            ->required()
                            ->default(true)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Monitor Settings')
                    ->schema([
                        Forms\Components\TextInput::make('interval')
                            ->required()
                            ->numeric()
                            ->default(5)
                            ->step(1)
                            ->minValue(1)
                            ->helperText('Check interval in minutes'),
                        Forms\Components\TextInput::make('consecutive_threshold')
                            ->required()
                            ->numeric()
                            ->default(state: 2)
                            ->step(1)
                            ->minValue(1)
                            ->helperText('Number of failed checks in a row needed before registering an anomaly and sending an alert'),
                        Forms\Components\TextInput::make('user_agent')
                            ->placeholder(config('app.name'))
                            ->hidden(fn (Get $get) => $get('type') !== MonitorType::HTTP->value)
                            ->maxLength(255)
                            ->helperText('Custom User-Agent string for HTTP requests')
                            ->live(),
                        Forms\Components\Select::make('alerts')
                            ->helperText('Alerts to send when the monitor is down')
                            ->multiple()
                            ->relationship('alerts', 'name', modifyQueryUsing: fn (Builder $query) => $query->where('user_id', auth()->id()))
                            ->preload(),
                        Forms\Components\Toggle::make('auto_create_update')
                            ->label('Post update when anomaly is detected')
                            ->helperText('Automatically create an update once an anomaly is detected (threshold reached) on the status pages where this monitor is being shown.')
                            ->default(true)
                            ->hintAction(
                                Forms\Components\Actions\Action::make('customize_text')
                                    ->modalHeading('Customize update text')
                                    ->modalFooter(fn () => new HtmlString('<div class="text-sm text-gray-500">Use the following variables in your update text: <code>:monitor_name</code>, <code>:monitor_address</code>, <code>:monitor_type</code></div>'))
                                    ->form([
                                        Forms\Components\TextInput::make('update_values.title')
                                            ->label('Update title')
                                            ->helperText('The title of the update that will be posted when an anomaly is detected.')
                                            ->default(':monitor_name is experiencing issues'),
                                        Forms\Components\MarkdownEditor::make('update_values.content')
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
                                    ->action(function (?Monitor $record, array $data, Forms\Set $set) {
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

                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('expects')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('type')
                    ->searchable()
                    ->description(fn ($record) => ! $record->is_enabled ? 'Inactive' : $record->interval.' min, '.$record->consecutive_threshold.'x'),
                Tables\Columns\IconColumn::make('is_enabled')
                    ->boolean()
                    ->label('Enabled')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('alerts.name')
                    ->size('xs')
                    ->label('Alerts')
                    ->wrap()
                    ->wrap(),
                Tables\Columns\TextColumn::make('last_checked_at')
                    ->since()
                    ->tooltip(fn (Monitor $record) => $record->last_checked_at?->format('j F Y, g:i a'))
                    ->description(fn (Monitor $record) => ($record->last_checkin_at ? 'Checked in '.$record->last_checkin_at?->diffForHumans() : null))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'ok' => 'OK',
                        'fail' => 'Failed',
                        'pending' => 'Pending',
                    ]),
                Tables\Filters\Filter::make('last_checked_at')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
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
            ->actions([
                Tables\Actions\Action::make('toggle_enabled')
                    ->label(null)
                    ->iconButton()
                    ->tooltip(fn (Monitor $record) => $record->is_enabled ? 'Disable' : 'Enable')
                    ->action(fn (Monitor $record) => $record->update(['is_enabled' => ! $record->is_enabled]))
                    ->icon('heroicon-o-power')
                    ->color(fn (Monitor $record) => $record->is_enabled ? 'success' : 'gray'),
                Tables\Actions\EditAction::make(),
            ])
            ->emptyStateHeading('Start monitoring your website')
            ->emptyStateDescription('Set up your first monitor to check the status of your website, API or other service.')
            ->emptyStateIcon('heroicon-o-heart')
            ->emptyStateActions([
                \Filament\Tables\Actions\CreateAction::make()
                    ->label('Create a monitor')
                    ->icon('heroicon-o-plus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('enable')
                        ->label('Enable')
                        ->action(fn ($records) => $records->each->update(['is_enabled' => true]))
                        ->deselectRecordsAfterCompletion()
                        ->icon('heroicon-o-check'),
                    Tables\Actions\BulkAction::make('disable')
                        ->label('Disable')
                        ->action(fn ($records) => $records->each->update(['is_enabled' => false]))
                        ->deselectRecordsAfterCompletion()
                        ->icon('heroicon-o-x-mark'),
                    Tables\Actions\BulkAction::make('set_alerts')
                        ->label('Set Alerts')
                        ->form([
                            Forms\Components\Select::make('alerts')
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
                    Tables\Actions\DeleteBulkAction::make(),

                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AlertsRelationManager::class,
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
