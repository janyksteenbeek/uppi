<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use App\Traits\WithoutUserScopes;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;

class UserResource extends Resource
{
    use WithoutUserScopes;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Account Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('Email verified at')
                            ->helperText('Leave empty to mark email as unverified'),
                    ])->columns(3),

                Forms\Components\Section::make('Security')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => $state ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255)
                            ->helperText(fn (string $operation) => $operation === 'edit' ? 'Leave empty to keep current password' : null),
                        Forms\Components\Toggle::make('is_admin')
                            ->label('Administrator')
                            ->helperText('Administrators can access the backstage panel'),
                    ])->columns(2),

                Forms\Components\Section::make('Feature Flags')
                    ->description('Enable experimental features for this user')
                    ->schema([
                        Forms\Components\CheckboxList::make('feature_flags')
                            ->options(User::availableFeatureFlags())
                            ->label('')
                            ->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('monitors_count')
                    ->label('Monitors')
                    ->counts('monitors')
                    ->sortable(),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->getStateUsing(fn (User $record) => $record->email_verified_at !== null)
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('feature_flags')
                    ->label('Features')
                    ->badge()
                    ->color('info')
                    ->getStateUsing(fn (User $record) => $record->feature_flags ?? []),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->since()
                    ->tooltip(fn (User $record) => $record->created_at->format('j F Y, g:i a'))
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_admin')
                    ->label('Admin status'),
                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Email verified')
                    ->nullable(),
                Tables\Filters\Filter::make('has_tests_feature')
                    ->label('Has tests feature')
                    ->query(fn ($query) => $query->whereJsonContains('feature_flags', 'run-tests')),
                Tables\Filters\Filter::make('has_server_monitoring_feature')
                    ->label('Has server monitoring feature')
                    ->query(fn ($query) => $query->whereJsonContains('feature_flags', 'server-monitoring')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('toggle_tests')
                        ->label(fn (User $user) => $user->hasFeature('run-tests') ? 'Disable tests' : 'Enable tests')
                        ->icon('heroicon-o-beaker')
                        ->color(fn (User $user) => $user->hasFeature('run-tests') ? 'danger' : 'success')
                        ->action(function (User $user) {
                            if ($user->hasFeature('run-tests')) {
                                $user->disableFeature('run-tests');
                                Notification::make()
                                    ->title('Tests disabled')
                                    ->body("Browser tests feature disabled for {$user->name}")
                                    ->warning()
                                    ->send();
                            } else {
                                $user->enableFeature('run-tests');
                                Notification::make()
                                    ->title('Tests enabled')
                                    ->body("Browser tests feature enabled for {$user->name}")
                                    ->success()
                                    ->send();
                            }
                        }),
                    Tables\Actions\Action::make('toggle_server_monitoring')
                        ->label(fn (User $user) => $user->hasFeature('server-monitoring') ? 'Disable server monitoring' : 'Enable server monitoring')
                        ->icon('heroicon-o-server')
                        ->color(fn (User $user) => $user->hasFeature('server-monitoring') ? 'danger' : 'success')
                        ->action(function (User $user) {
                            if ($user->hasFeature('server-monitoring')) {
                                $user->disableFeature('server-monitoring');
                                Notification::make()
                                    ->title('Server monitoring disabled')
                                    ->body("Server monitoring feature disabled for {$user->name}")
                                    ->warning()
                                    ->send();
                            } else {
                                $user->enableFeature('server-monitoring');
                                Notification::make()
                                    ->title('Server monitoring enabled')
                                    ->body("Server monitoring feature enabled for {$user->name}")
                                    ->success()
                                    ->send();
                            }
                        }),
                    Tables\Actions\Action::make('verify_email')
                        ->label('Verify email')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->visible(fn (User $user) => $user->email_verified_at === null)
                        ->action(function (User $user) {
                            $user->update(['email_verified_at' => now()]);
                            Notification::make()
                                ->title('Email verified')
                                ->success()
                                ->send();
                        }),
                    Impersonate::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('enable_tests')
                        ->label('Enable tests feature')
                        ->icon('heroicon-o-beaker')
                        ->action(function ($records) {
                            $records->each(fn (User $user) => $user->enableFeature('run-tests'));
                            Notification::make()
                                ->title('Tests enabled')
                                ->body('Browser tests feature enabled for selected users')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('disable_tests')
                        ->label('Disable tests feature')
                        ->icon('heroicon-o-beaker')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(fn (User $user) => $user->disableFeature('run-tests'));
                            Notification::make()
                                ->title('Tests disabled')
                                ->body('Browser tests feature disabled for selected users')
                                ->warning()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('enable_server_monitoring')
                        ->label('Enable server monitoring')
                        ->icon('heroicon-o-server')
                        ->action(function ($records) {
                            $records->each(fn (User $user) => $user->enableFeature('server-monitoring'));
                            Notification::make()
                                ->title('Server monitoring enabled')
                                ->body('Server monitoring feature enabled for selected users')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('disable_server_monitoring')
                        ->label('Disable server monitoring')
                        ->icon('heroicon-o-server')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(fn (User $user) => $user->disableFeature('server-monitoring'));
                            Notification::make()
                                ->title('Server monitoring disabled')
                                ->body('Server monitoring feature disabled for selected users')
                                ->warning()
                                ->send();
                        }),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
