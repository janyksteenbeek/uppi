<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PersonalAccessTokenResource\Pages;
use App\Filament\Resources\PersonalAccessTokenResource\RelationManagers;
use Filament\Forms;
use Filament\Notifications\Notification;
use Laravel\Sanctum\PersonalAccessToken;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PersonalAccessTokenResource extends Resource
{
    protected static ?string $model = PersonalAccessToken::class;

   protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Connections';
    public static ?string $label = 'Connections';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('created_at'),
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->since()
                    ->badge()
                    ->color(fn($state) => $state->isPast() ? 'danger' : (now()->diffInHours($state) <= 1 ? 'warning' : 'success')),
            ])
            ->filters([
                Tables\Filters\Filter::make('expired')
                    ->query(fn (Builder $query) => $query->where('expires_at', '>', now()))
                    ->label('Not expired')
                    ->default(true),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create_token')
                    ->icon('heroicon-o-plus')
                    ->label('Register new mobile device')
                    ->action(function () {
                        $activationCode = rand(100000, 999999);

                        $token = auth()->user()->createToken('Mobile device (not activated)', expiresAt: now()->addMinutes(15))->accessToken;
                        $token->activation_code = $activationCode;
                        $token->save();

                        Notification::make()
                            ->title('Log in to the mobile app with the following code:')
                            ->body('<div class="flex flex-row flex-inline gap-2">
                            '.implode('', array_map(fn($item) => '<div class="h-10 border-2 p-2 rounded shadow-sm">'.$item.'</div>', str_split($activationCode)))
                            . '</div>'
                            . '<div class="text-xs mt-2 text-gray-500">This code will expire in 15 minutes.</div>')
                            ->success()
                            ->inline()
                            ->persistent()
                            ->send();
                    })
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePersonalAccessTokens::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}