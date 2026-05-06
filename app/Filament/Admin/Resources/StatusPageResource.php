<?php

namespace App\Filament\Admin\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Admin\Resources\StatusPageResource\Pages\ListStatusPages;
use App\Filament\Admin\Resources\StatusPageResource\Pages\CreateStatusPage;
use App\Filament\Admin\Resources\StatusPageResource\Pages\EditStatusPage;
use App\Filament\Admin\Resources\StatusPageResource\Pages;
use App\Models\StatusPage;
use App\Traits\WithoutUserScopes;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StatusPageResource extends Resource
{
    use WithoutUserScopes;

    protected static ?string $model = StatusPage::class;

    protected static string | \BackedEnum | null $navigationIcon = 'phosphor-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_enabled')
                    ->required(),
                TextInput::make('logo_url')
                    ->maxLength(255)
                    ->default(null),
                TextInput::make('website_url')
                    ->maxLength(255)
                    ->default(null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                IconColumn::make('is_enabled')
                    ->boolean(),
                TextColumn::make('logo_url')
                    ->searchable(),
                TextColumn::make('website_url')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            'index' => ListStatusPages::route('/'),
            'create' => CreateStatusPage::route('/create'),
            'edit' => EditStatusPage::route('/{record}/edit'),
        ];
    }
}
