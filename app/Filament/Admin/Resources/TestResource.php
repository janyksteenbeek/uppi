<?php

namespace App\Filament\Admin\Resources;

use App\Enums\Tests\TestFlowBlockType;
use App\Enums\Tests\TestStatus;
use App\Filament\Admin\Resources\TestResource\Pages;
use App\Models\Test;
use App\Traits\WithoutUserScopes;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestResource extends Resource
{
    use WithoutUserScopes;

    protected static ?string $model = Test::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('entrypoint_url')
                            ->required()
                            ->url()
                            ->label('Entrypoint URL'),
                    ])->columns(3),

                Forms\Components\Section::make('Test flow')
                    ->schema([
                        Forms\Components\Repeater::make('steps')
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->label('')
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->options(TestFlowBlockType::options())
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('value', null))
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('value')
                                    ->label(fn (Get $get) => TestFlowBlockType::tryFrom($get('type'))?->getValueLabel() ?? 'Value')
                                    ->required(fn (Get $get) => TestFlowBlockType::tryFrom($get('type'))?->requiresValue() ?? false)
                                    ->visible(fn (Get $get) => TestFlowBlockType::tryFrom($get('type'))?->requiresValue() ?? false)
                                    ->columnSpan(fn (Get $get) => TestFlowBlockType::tryFrom($get('type'))?->requiresSelector() ? 1 : 2),
                                Forms\Components\TextInput::make('selector')
                                    ->label(fn (Get $get) => TestFlowBlockType::tryFrom($get('type'))?->getSelectorLabel() ?? 'Selector')
                                    ->required(fn (Get $get) => TestFlowBlockType::tryFrom($get('type'))?->requiresSelector() ?? false)
                                    ->visible(fn (Get $get) => TestFlowBlockType::tryFrom($get('type'))?->requiresSelector() ?? false)
                                    ->columnSpan(1),
                            ])
                            ->columns(2)
                            ->reorderable()
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(fn (array $state): ?string => TestFlowBlockType::tryFrom($state['type'] ?? '')?->getLabel() . (isset($state['value']) && $state['value'] ? ': ' . \Str::limit($state['value'], 30) : (isset($state['selector']) && $state['selector'] ? ': ' . \Str::limit($state['selector'], 30) : '')))
                            ->addActionLabel('Add step')
                            ->defaultItems(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lastRun.status')
                    ->label('Status')
                    ->badge()
                    ->default('-'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('entrypoint_url')
                    ->label('URL')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('steps_count')
                    ->label('Steps')
                    ->counts('steps'),
                Tables\Columns\TextColumn::make('monitors_count')
                    ->label('Monitors')
                    ->counts('monitors'),
                Tables\Columns\TextColumn::make('last_run_at')
                    ->label('Last run')
                    ->since()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('last_run_status')
                    ->label('Status')
                    ->options(TestStatus::options())
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        return $query->whereHas('lastRun', fn (Builder $q) => $q->where('status', $data['value']));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListTests::route('/'),
            'create' => Pages\CreateTest::route('/create'),
            'edit' => Pages\EditTest::route('/{record}/edit'),
        ];
    }
}
