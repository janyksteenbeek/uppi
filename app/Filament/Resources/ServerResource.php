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
        return parent::getEloquentQuery()->where('user_id', Auth::id());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Server Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('hostname')
                            ->required()
                            ->maxLength(255)
                            ->helperText('FQDN or hostname of the server'),
                        Forms\Components\TextInput::make('ip_address')
                            ->required()
                            ->ip()
                            ->maxLength(45)
                            ->helperText('IP address of the server'),
                        Forms\Components\TextInput::make('os')
                            ->maxLength(255)
                            ->placeholder('e.g., Ubuntu 22.04, CentOS 8, Windows Server 2019')
                            ->helperText('Operating system information'),
                        Forms\Components\Toggle::make('is_active')
                            ->required()
                            ->default(true)
                            ->helperText('Enable monitoring for this server'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Authentication')
                    ->schema([
                        Forms\Components\TextInput::make('secret')
                            ->label('Secret Key')
                            ->password()
                            ->revealable()
                            ->maxLength(64)
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn (?Server $record) => $record?->secret)
                            ->helperText('This secret is used for HMAC authentication. Copy this to configure your monitoring daemon.')
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
                                ->modalDescription('Are you sure you want to regenerate the secret key? You will need to update your monitoring daemon configuration.')
                                ->action(fn (Server $record) => $record->generateNewSecret())
                                ->visible(fn (?Server $record) => $record && $record->exists),
                        ])->columnSpanFull(),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('online_status')
                    ->label('Status')
                    ->icon(fn (Server $record) => $record->isOnline() ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn (Server $record) => $record->isOnline() ? 'success' : 'danger'),
                
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('hostname')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('os')
                    ->label('OS')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                
                Tables\Columns\TextColumn::make('last_seen_at')
                    ->label('Last Seen')
                    ->since()
                    ->tooltip(fn (Server $record) => $record->last_seen_at?->format('j F Y, g:i a'))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('metrics_count')
                    ->label('Metrics')
                    ->counts('metrics')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                
                Tables\Filters\Filter::make('online')
                    ->query(fn (Builder $query): Builder => $query->where('last_seen_at', '>=', now()->subMinutes(5)))
                    ->label('Online Servers'),
                
                Tables\Filters\Filter::make('last_seen')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('last_seen_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('last_seen_at', '<=', $date),
                            );
                    })
                    ->label('Last Seen Date Range'),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle_active')
                    ->label(null)
                    ->iconButton()
                    ->tooltip(fn (Server $record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->action(fn (Server $record) => $record->update(['is_active' => ! $record->is_active]))
                    ->icon('heroicon-o-power')
                    ->color(fn (Server $record) => $record->is_active ? 'success' : 'gray'),
                
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion()
                        ->icon('heroicon-o-check'),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion()
                        ->icon('heroicon-o-x-mark'),
                    
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
            'index' => Pages\ListServers::route('/'),
            'create' => Pages\CreateServer::route('/create'),
            'edit' => Pages\EditServer::route('/{record}/edit'),
            'view' => Pages\ViewServer::route('/{record}'),
        ];
    }
}