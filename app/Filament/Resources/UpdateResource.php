<?php

namespace App\Filament\Resources;

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\UpdateResource\Pages\ListUpdates;
use App\Filament\Resources\UpdateResource\Pages\CreateUpdate;
use App\Filament\Resources\UpdateResource\Pages\EditUpdate;
use App\Enums\StatusPage\UpdateStatus;
use App\Enums\StatusPage\UpdateType;
use App\Filament\Resources\UpdateResource\Pages;
use App\Models\Update;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UpdateResource extends Resource
{
    protected static ?string $model = Update::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-newspaper';

    protected static string | \UnitEnum | null $navigationGroup = 'Status Pages';

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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Status')
                    ->hiddenLabel()
                    ->heading(null)
                    ->schema([
                        ToggleButtons::make('status')
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
                Section::make('Content')
                    ->heading(null)
                    ->schema([
                        Grid::make()
                            ->columns(3)
                            ->schema([
                                Grid::make()
                                    ->schema([
                                        TextInput::make('title')
                                            ->required()
                                            ->maxLength(255)
                                            ->live()
                                            ->debounce(delay: 250)
                                            ->columnSpanFull()
                                            ->afterStateUpdated(fn (Set $set, $state) => $set('slug', str($state)->slug())),
                                        MarkdownEditor::make('content')
                                            ->required()
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpan(2),
                                Grid::make()
                                    ->schema([
                                        Select::make('type')
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
                                        FileUpload::make('image')
                                            ->image()
                                            ->maxSize(2048)
                                            ->directory('updates')
                                            ->columnSpanFull()
                                            ->helperText('Optional: Add an image to your update'),
                                        Toggle::make('is_featured')
                                            ->label('Featured')
                                            ->helperText('Pin this update to the top'),
                                    ])
                                    ->columnSpan(1),
                            ]),
                    ]),

                Section::make('Impact')
                    ->collapsible()
                    ->icon('heroicon-o-clock')
                    ->schema([
                        DateTimePicker::make('from')
                            ->label('Start Date')
                            ->helperText('When does this update start?'),
                        DateTimePicker::make('to')
                            ->label('End Date')
                            ->helperText('When does this update end?'),
                        Select::make('monitors')
                            ->multiple()
                            ->relationship('monitors', 'name', modifyQueryUsing: fn (Builder $query) => $query->where('user_id', auth()->id()))
                            ->helperText('What monitors are impacted?'),
                    ])->columns(2),

                Section::make('Metadata')
                    ->collapsible()
                    ->icon('heroicon-o-cog')
                    ->schema([
                        Select::make('status_pages')
                            ->multiple()
                            ->relationship(
                                'statusPages',
                                'name',
                                modifyQueryUsing: fn (Builder $query) => $query->where('user_id', auth()->id()))
                            ->preload()
                            ->searchable()
                            ->helperText('Select the status pages to which this update should be added'),
                        TextInput::make('slug')
                            ->required()
                            ->live()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('The URL-friendly version of the title'),

                        Toggle::make('is_published')
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
                TextColumn::make('title')
                    ->searchable()
                    ->limit(50)
                    ->wrap(),
                TextColumn::make('type'),
                TextColumn::make('status')
                    ->badge(),
                IconColumn::make('is_published')
                    ->boolean()
                    ->label('Published'),
                IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured'),
                TextColumn::make('from')
                    ->dateTime()
                    ->size('xs')
                    ->sortable(),
                TextColumn::make('to')
                    ->dateTime()
                    ->size('xs')
                    ->sortable(),
                TextColumn::make('monitors.name')
                    ->label('Monitors')
                    ->wrap(),
                TextColumn::make('statusPages.name')
                    ->label('Status Pages')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(UpdateStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->emptyStateHeading(heading: 'You haven\'t shared any updates yet')
            ->emptyStateDescription('Updates are a great way to keep your users informed about what\'s happening with your service. Announce maintenance, outages, and other important updates.')
            ->emptyStateIcon('heroicon-o-newspaper')
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Create update')
                    ->icon('heroicon-o-plus'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label('Publish')
                        ->action(fn ($records) => $records->each->update(['is_published' => true]))
                        ->deselectRecordsAfterCompletion()
                        ->icon('heroicon-o-check'),
                    BulkAction::make('unpublish')
                        ->label('Unpublish')
                        ->action(fn ($records) => $records->each->update(['is_published' => false]))
                        ->deselectRecordsAfterCompletion()
                        ->icon('heroicon-o-x-mark'),
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
            'index' => ListUpdates::route('/'),
            'create' => CreateUpdate::route('/create'),
            'edit' => EditUpdate::route('/{record}/edit'),
        ];
    }
}
