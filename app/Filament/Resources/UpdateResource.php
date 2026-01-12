<?php

namespace App\Filament\Resources;

use App\Enums\StatusPage\UpdateStatus;
use App\Enums\StatusPage\UpdateType;
use App\Filament\Resources\UpdateResource\Pages;
use App\Models\Update;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UpdateResource extends Resource
{
    protected static ?string $model = Update::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationGroup = 'Status Pages';

    protected static ?int $navigationSort = 2;

    protected static function getStatusCollection(Get $get): Collection
    {
        $type = $get('type');
        if (! $type) {
            return collect([]);
        }

        $updateType = is_string($type) ? UpdateType::tryFrom($type) : $type;

        return collect(UpdateStatus::cases())
            ->filter(fn ($status) => in_array(
                $status->value,
                array_column($updateType->getAvailableStatuses(), 'value')
            ));
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Status')
                    ->hiddenLabel()
                    ->heading(null)
                    ->schema([
                        Forms\Components\ToggleButtons::make('status')
                            ->label('Current Status')
                            ->hiddenLabel()
                            ->options(fn (Get $get) => static::getStatusCollection($get)
                                ->mapWithKeys(fn ($status) => [$status->value => $status?->getLabel()]))
                            ->icons(fn (Get $get) => static::getStatusCollection($get)
                                ->mapWithKeys(fn ($status) => [$status->value => $status?->getIcon()]))
                            ->colors(fn (Get $get) => static::getStatusCollection($get)
                                ->mapWithKeys(fn ($status) => [$status->value => $status?->getColor()]))
                            ->grouped()
                            ->default(UpdateStatus::NEW)
                            ->live()
                            ->required()
                            ->inline()
                            ->afterStateUpdated(function ($record, $state) {
                                if (! $record) {
                                    return;
                                }
                                $record->update(['status' => $state]);

                                Notification::make()
                                    ->title('Status updated to '.$state)
                                    ->success()
                                    ->send();
                            })
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Content')
                    ->heading(null)
                    ->schema([
                        Forms\Components\Grid::make()
                            ->columns(3)
                            ->schema([
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('title')
                                            ->required()
                                            ->maxLength(255)
                                            ->live()
                                            ->debounce(delay: 250)
                                            ->columnSpanFull()
                                            ->afterStateUpdated(fn (Set $set, $state) => $set('slug', str($state)->slug())),
                                        Forms\Components\MarkdownEditor::make('content')
                                            ->required()
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpan(2),
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\Select::make('type')
                                            ->required()
                                            ->enum(UpdateType::class)
                                            ->disablePlaceholderSelection()
                                            ->options(UpdateType::class)
                                            ->live()
                                            ->prefixIcon(function (Get $get) {
                                                $type = $get('type');
                                                if (! $type) {
                                                    return null;
                                                }
                                                $updateType = is_string($type) ? UpdateType::tryFrom($type) : $type;

                                                return $updateType?->getIcon();
                                            })
                                            ->default(state: UpdateType::UPDATE)
                                            ->columnSpanFull(),
                                        Forms\Components\FileUpload::make('image')
                                            ->image()
                                            ->maxSize(2048)
                                            ->directory('updates')
                                            ->columnSpanFull()
                                            ->helperText('Optional: Add an image to your update'),
                                        Forms\Components\Toggle::make('is_featured')
                                            ->label('Featured')
                                            ->helperText('Pin this update to the top'),
                                    ])
                                    ->columnSpan(1),
                            ]),
                    ]),

                Forms\Components\Section::make('Impact')
                    ->collapsible()
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Forms\Components\DateTimePicker::make('from')
                            ->label('Start Date')
                            ->helperText('When does this update start?'),
                        Forms\Components\DateTimePicker::make('to')
                            ->label('End Date')
                            ->helperText('When does this update end?'),
                        Forms\Components\Select::make('monitors')
                            ->multiple()
                            ->relationship('monitors', 'name', modifyQueryUsing: fn (Builder $query) => $query->where('user_id', auth()->id()))
                            ->helperText('What monitors are impacted?'),
                    ])->columns(2),

                Forms\Components\Section::make('Metadata')
                    ->collapsible()
                    ->icon('heroicon-o-cog')
                    ->schema([
                        Forms\Components\Select::make('status_pages')
                            ->multiple()
                            ->relationship(
                                'statusPages',
                                'name',
                                modifyQueryUsing: fn (Builder $query) => $query->where('user_id', auth()->id()))
                            ->preload()
                            ->searchable()
                            ->helperText('Select the status pages to which this update should be added'),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->live()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('The URL-friendly version of the title'),

                        Forms\Components\Toggle::make('is_published')
                            ->label('Published')
                            ->helperText('Make this update visible to everyone')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(50)
                    ->wrap(),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_published')
                    ->boolean()
                    ->label('Published'),
                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured'),
                Tables\Columns\TextColumn::make('from')
                    ->dateTime()
                    ->size('xs')
                    ->sortable(),
                Tables\Columns\TextColumn::make('to')
                    ->dateTime()
                    ->size('xs')
                    ->sortable(),
                Tables\Columns\TextColumn::make('monitors.name')
                    ->label('Monitors')
                    ->wrap(),
                Tables\Columns\TextColumn::make('statusPages.name')
                    ->label('Status Pages')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(UpdateStatus::class),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->emptyStateHeading(heading: 'You haven\'t shared any updates yet')
            ->emptyStateDescription('Updates are a great way to keep your users informed about what\'s happening with your service. Announce maintenance, outages, and other important updates.')
            ->emptyStateIcon('heroicon-o-newspaper')
            ->emptyStateActions([
                \Filament\Tables\Actions\CreateAction::make()
                    ->label('Create update')
                    ->icon('heroicon-o-plus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('publish')
                        ->label('Publish')
                        ->action(fn ($records) => $records->each->update(['is_published' => true]))
                        ->deselectRecordsAfterCompletion()
                        ->icon('heroicon-o-check'),
                    Tables\Actions\BulkAction::make('unpublish')
                        ->label('Unpublish')
                        ->action(fn ($records) => $records->each->update(['is_published' => false]))
                        ->deselectRecordsAfterCompletion()
                        ->icon('heroicon-o-x-mark'),
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
            'index' => Pages\ListUpdates::route('/'),
            'create' => Pages\CreateUpdate::route('/create'),
            'edit' => Pages\EditUpdate::route('/{record}/edit'),
        ];
    }
}
