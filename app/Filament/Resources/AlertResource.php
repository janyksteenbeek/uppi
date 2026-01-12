<?php

namespace App\Filament\Resources;

use App\Enums\Alerts\AlertType;
use App\Filament\Resources\AlertResource\Pages;
use App\Models\Alert;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use NotificationChannels\Telegram\TelegramUpdates;

final class AlertResource extends Resource
{
    protected static ?string $model = Alert::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationLabel = 'Alerts';

    protected static ?string $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 3;

    public static function registrationCode(): string
    {
        return auth()->user()->id.':'.md5(date('Y-m-d').auth()->user()->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\ToggleButtons::make('type')
                    ->options(AlertType::class)
                    ->required()
                    ->inline()
                    ->grouped()
                    ->live()
                    ->columnSpanFull(),

                Forms\Components\Section::make([
                    Forms\Components\Hidden::make('uppi_app_info')
                        ->dehydrated(false)
                        ->required(fn (Get $get) => $get('type') === AlertType::EXPO->value),
                    Forms\Components\View::make('filament.forms.components.uppi-app-info')
                        ->viewData([
                            'personal_access_tokens_url' => PersonalAccessTokenResource::getUrl(),
                        ]),
                ])
                    ->columnSpanFull()
                    ->visible(fn (Get $get) => AlertType::tryFrom($get('type')) === AlertType::EXPO),

                Forms\Components\TextInput::make('name')
                    ->required()
                    ->columnSpanFull()
                    ->live()
                    ->visible(fn (Get $get, $context) => $context === 'edit' || ($context === 'create' && AlertType::tryFrom($get('type')) !== AlertType::EXPO)),

                Forms\Components\TextInput::make('destination')
                    ->helperText(function (Get $get) {
                        return match (AlertType::tryFrom($get('type'))) {
                            AlertType::EMAIL => 'The email address to send the alert to.',
                            AlertType::SLACK => 'The Slack channel to send the alert to.',
                            AlertType::BIRD => 'The phone number to send the alert to.',
                            AlertType::MESSAGEBIRD => 'The phone number to send the alert to.',
                            AlertType::TELEGRAM => 'The Telegram chat ID to send the alert to.',
                            AlertType::PUSHOVER => 'Your PushOver User Key. Failure alerts will be sent with a emergency priority every 60 seconds for 3 minutes. Recovery alerts will be sent with a high priority.',
                            default => null,
                        };
                    })
                    ->prefix(fn (Get $get) => AlertType::tryFrom($get('type')) === AlertType::SLACK ? '#' : null)
                    ->password(fn (Get $get) => AlertType::tryFrom($get('type')) === AlertType::PUSHOVER)
                    ->live()
                    ->columnSpanFull()
                    ->hidden(fn (Get $get) => AlertType::tryFrom($get('type')) === AlertType::EXPO)
                    ->visible(fn (Get $get) => ! empty($get('type')))
                    ->email(fn (Get $get) => AlertType::tryFrom($get('type')) === AlertType::EMAIL)
                    ->required(),

                Forms\Components\Toggle::make('is_enabled')
                    ->required()
                    ->default(true)
                    ->hidden(fn (Get $get) => AlertType::tryFrom($get('type')) === AlertType::EXPO)
                    ->columnSpanFull(),

                Forms\Components\Section::make([
                    Forms\Components\TextInput::make('config.slack_token')
                        ->label('Slack Bot OAuth Token')
                        ->required(),
                ])
                    ->columnSpanFull()
                    ->live()
                    ->visible(fn (Get $get) => AlertType::tryFrom($get('type')) === AlertType::SLACK),

                Forms\Components\Section::make([
                    Forms\Components\TextInput::make('config.bird_api_key')
                        ->required()
                        ->password()
                        ->label('API Key')
                        ->helperText('The API key for the Bird API.'),
                    Forms\Components\TextInput::make('config.bird_workspace_id')
                        ->label('Workspace ID')
                        ->helperText('The ID of the workspace that will be used to send the alert from.')
                        ->required(),
                    Forms\Components\TextInput::make('config.bird_channel_id')
                        ->label('Channel ID')
                        ->helperText('The ID of the channel that will be used to send the alert to.')
                        ->required(),
                ])
                    ->columnSpanFull()
                    ->live()
                    ->visible(fn (Get $get) => AlertType::tryFrom($get('type')) === AlertType::BIRD),

                Forms\Components\Section::make([
                    Forms\Components\TextInput::make('config.pushover_api_token')
                        ->required()
                        ->password()
                        ->label('Application API Token')
                        ->helperText('The Application API Token for the PushOver API.')
                        ->hintAction(
                            \Filament\Forms\Components\Actions\Action::make('generate')
                                ->label('Create a new application')
                                ->icon('heroicon-m-arrow-top-right-on-square')
                                ->url('https://pushover.net/apps/build')
                                ->openUrlInNewTab()
                        ),
                ])
                    ->columnSpanFull()
                    ->live()
                    ->visible(fn (Get $get) => AlertType::tryFrom($get('type')) === AlertType::PUSHOVER),

                Forms\Components\Section::make([
                    Forms\Components\Section::make([

                        Placeholder::make('telegram_bot_token')
                            ->label('Registering with our Telegram bot')
                            ->content('In order to receive alerts, you must register with our bot, @uppialertbot. Click the button below to open the bot, and paste the command "/register YOUR_USER_ID" to register.')
                            ->helperText('After registering, you won\'t receive confirmation in Telegram. Press the button below to get your Chat ID.')
                            ->columnSpanFull(),

                        Actions::make([
                            Forms\Components\Actions\Action::make('register')
                                ->label('@uppialertbot on Telegram')
                                ->outlined()
                                ->icon('heroicon-o-arrow-top-right-on-square')
                                ->url('https://t.me/uppialertbot')
                                ->openUrlInNewTab(),
                        ]),

                        Forms\Components\TextInput::make('config.telegram_bot_token')
                            ->dehydrated(false)
                            ->required()
                            ->label('Paste the following command in a chat with @uppialertbot')
                            ->formatStateUsing(fn () => '/register '.self::registrationCode())
                            ->helperText('After sending the command, please click the button below'),

                        Actions::make([
                            Forms\Components\Actions\Action::make('get_chat_id')
                                ->label('I have registered with the bot on Telegram. Attach my chat ID to this alert')
                                ->icon('heroicon-o-check')
                                ->action(function (Set $set) {
                                    $updates = collect(
                                        TelegramUpdates::create()
                                            ->latest()
                                            ->options([
                                                'timeout' => 2,
                                            ])
                                            ->get()['result'])->where('message.text', '/register '.self::registrationCode())->first();

                                    if (! $updates) {
                                        Notification::make()
                                            ->title('We couldn\'t find your registration command')
                                            ->body('Make sure you\'re talking to @uppialertbot and not another bot. Try to send the registration command again, and if the problem persists, try again later.')
                                            ->danger()
                                            ->send();

                                        return;
                                    }

                                    $set('destination', $updates['message']['chat']['id']);

                                    Notification::make()
                                        ->title('Your chat ID has been attached to this alert')
                                        ->body('You can now send alerts to this chat: '.$updates['message']['chat']['username'])
                                        ->success()
                                        ->send();
                                }),
                        ]),
                    ]),
                ])
                    ->visible(fn (Get $get) => AlertType::tryFrom($get('type')) === AlertType::TELEGRAM),

                Forms\Components\Section::make([
                    Forms\Components\TextInput::make('config.bird_api_key')
                        ->required()
                        ->password()
                        ->label('API Key')
                        ->helperText('The API key for the MessageBird API.'),
                    Forms\Components\TextInput::make('config.bird_originator')
                        ->label('Originator')
                        ->helperText('The originator of the message. This is the name that will be displayed on the recipient\'s phone.')
                        ->required(),
                ])
                    ->columnSpanFull()
                    ->live()
                    ->visible(fn (Get $get) => AlertType::tryFrom($get('type')) === AlertType::MESSAGEBIRD),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->description(fn ($record) => ! $record->is_enabled ? 'Inactive' : null),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('destination')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        if (in_array($record->type, [AlertType::PUSHOVER, AlertType::EXPO])) {
                            return '************';
                        }

                        return $state;
                    }),
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
            ->emptyStateHeading('No alerts set up yet')
            ->emptyStateDescription('Set up alerts to different destinations to notify you when something is wrong.')
            ->emptyStateIcon('heroicon-o-bell-alert')
            ->emptyStateActions([
                \Filament\Tables\Actions\CreateAction::make()
                    ->label('Create alert')
                    ->icon('heroicon-o-plus'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAlerts::route('/'),
        ];
    }
}
