<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ErrorProjectResource\Pages;
use App\Models\ErrorTracking\Project;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ErrorProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|\BackedEnum|null $navigationIcon = 'phosphor-folder-open';

    protected static ?string $navigationLabel = 'Projects';

    protected static string|\UnitEnum|null $navigationGroup = 'Error Tracking';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->visible(fn ($context) => $context === 'edit')
                    ->schema([
                        View::make('filament.error-tracking.sdk-setup'),
                    ])
                    ->columnSpanFull(),

                Section::make('Project details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, $context) {
                                if ($context === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            })
                            ->columnSpanFull(),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(140)
                            ->helperText('Used in the project URL — lower-case, no spaces.'),
                        Select::make('platform')
                            ->options([
                                'php' => 'PHP',
                                'laravel' => 'Laravel',
                                'javascript' => 'JavaScript',
                                'python' => 'Python',
                                'node' => 'Node.js',
                                'other' => 'Other',
                            ])
                            ->default('php')
                            ->live()
                            ->native(false),
                        Toggle::make('is_active')
                            ->default(true)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('slug')
                    ->color('gray')
                    ->fontFamily('mono')
                    ->copyable(),
                TextColumn::make('platform')
                    ->badge(),
                TextColumn::make('internal_id')
                    ->label('Project ID')
                    ->fontFamily('mono')
                    ->color('gray'),
                TextColumn::make('issues_count')
                    ->label('Issues')
                    ->counts('issues'),
                TextColumn::make('last_event_at')
                    ->label('Last event')
                    ->since()
                    ->placeholder('—'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListErrorProjects::route('/'),
            'create' => Pages\CreateErrorProject::route('/create'),
            'edit' => Pages\EditErrorProject::route('/{record}/edit'),
        ];
    }
}
