<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServerResource\Pages;
use App\Models\Server;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ServerResource extends Resource
{
    protected static ?string $model = Server::class;

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationIcon = 'heroicon-o-server';

    protected static ?string $navigationGroup = 'Monitoring';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::check() && Auth::user()->hasFeature('server-monitoring');
    }

    public static function getNavigationBadge(): ?string
    {
        if (! Auth::check()) {
            return null;
        }

        $count = Auth::user()->servers()->where('is_active', true)->count();

        if ($count === 0) {
            return null;
        }

        return (string) $count;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Server Information')
                    ->description(fn (string $operation) => $operation === 'create'
                        ? 'Give your server a name. After creation, you\'ll get an install command to run on your server.'
                        : null)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Production Web Server, Database Server')
                            ->helperText('A friendly name to identify this server'),
                        Forms\Components\TextInput::make('hostname')
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (string $operation) => $operation === 'edit')
                            ->helperText('Reported by the monitoring agent'),
                        Forms\Components\TextInput::make('ip_address')
                            ->label('IP Address')
                            ->maxLength(45)
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (string $operation) => $operation === 'edit')
                            ->helperText('Reported by the monitoring agent'),
                        Forms\Components\TextInput::make('os')
                            ->label('Operating System')
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (string $operation) => $operation === 'edit')
                            ->helperText('Reported by the monitoring agent'),
                        Forms\Components\Toggle::make('is_active')
                            ->required()
                            ->default(true)
                            ->visible(fn (string $operation) => $operation === 'edit')
                            ->helperText('Enable or disable monitoring for this server'),
                    ])->columns(2),

                Forms\Components\Section::make('Authentication')
                    ->visible(fn (string $operation) => $operation === 'edit')
                    ->schema([
                        Forms\Components\TextInput::make('secret')
                            ->label('Secret Key')
                            ->password()
                            ->revealable()
                            ->maxLength(64)
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn (?Server $record) => $record?->secret)
                            ->helperText('This secret is used for HMAC authentication.')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('copy_secret')
                                    ->label('Copy')
                                    ->icon('heroicon-o-clipboard')
                                    ->action(fn () => null)
                                    ->extraAttributes(fn ($state) => [
                                        'x-data' => '',
                                        'data-copy' => $state,
                                        'x-on:click' => 'navigator.clipboard.writeText($el.dataset.copy); $tooltip("Secret copied")',
                                    ])
                            ),

                        Actions::make([
                            Forms\Components\Actions\Action::make('regenerate_secret')
                                ->label('Regenerate Secret')
                                ->color('warning')
                                ->icon('heroicon-o-arrow-path')
                                ->requiresConfirmation()
                                ->modalHeading('Regenerate Secret Key')
                                ->modalDescription('Are you sure you want to regenerate the secret key? You will need to reinstall the monitoring agent on your server.')
                                ->action(fn (Server $record) => $record->generateNewSecret()),
                        ])->columnSpanFull(),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('status_display')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function (Server $record) {
                        if ($record->last_seen_at === null) {
                            return 'Waiting for agent';
                        }
                        if ($record->isOnline()) {
                            return 'Online';
                        }

                        return 'Offline';
                    })
                    ->color(fn (string $state) => match ($state) {
                        'Online' => 'success',
                        'Offline' => 'danger',
                        'Waiting for agent' => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn (string $state) => match ($state) {
                        'Online' => 'heroicon-o-check-circle',
                        'Offline' => 'heroicon-o-x-circle',
                        'Waiting for agent' => 'heroicon-o-clock',
                        default => null,
                    }),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Server $record) => $record->hostname),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('os')
                    ->label('OS')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('last_seen_at')
                    ->label('Last Seen')
                    ->since()
                    ->placeholder('Never')
                    ->tooltip(fn (Server $record) => $record->last_seen_at?->format('j F Y, g:i a'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('waiting')
                    ->query(fn (Builder $query): Builder => $query->whereNull('last_seen_at'))
                    ->label('Waiting for Agent'),

                Tables\Filters\Filter::make('online')
                    ->query(fn (Builder $query): Builder => $query->where('last_seen_at', '>=', now()->subMinutes(5)))
                    ->label('Online'),

                Tables\Filters\Filter::make('offline')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('last_seen_at')
                        ->where('last_seen_at', '<', now()->subMinutes(5)))
                    ->label('Offline'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Server $record) => $record->last_seen_at === null),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Start monitoring your servers')
            ->emptyStateDescription('Add your first server to monitor CPU, memory, disk space and network usage.')
            ->emptyStateIcon('heroicon-o-server')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add server')
                    ->icon('heroicon-o-plus'),
            ])
            ->recordUrl(fn (Server $record) => ServerResource::getUrl('view', ['record' => $record]));
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
            'index' => Pages\ListServers::route('/'),
            'create' => Pages\CreateServer::route('/create'),
            'edit' => Pages\EditServer::route('/{record}/edit'),
            'view' => Pages\ViewServer::route('/{record}'),
        ];
    }
}
