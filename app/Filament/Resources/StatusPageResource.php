<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use App\Filament\Resources\StatusPageResource\Pages\ListStatusPages;
use App\Filament\Resources\StatusPageResource\Pages\CreateStatusPage;
use App\Filament\Resources\StatusPageResource\Pages\EditStatusPage;
use App\Filament\Resources\StatusPageResource\Pages;
use App\Filament\Resources\StatusPageResource\RelationManagers\ItemsRelationManager;
use App\Models\StatusPage;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StatusPageResource extends Resource
{
    protected static ?string $model = StatusPage::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-eye';

    protected static string | \UnitEnum | null $navigationGroup = 'Status Pages';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->prefix(url('s/'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Toggle::make('is_enabled')
                            ->required()
                            ->default(true)
                            ->helperText('Enable or disable this status page'),
                    ])->columns(2),

                Section::make('Appearance')
                    ->schema([
                        FileUpload::make('logo_url')
                            ->image()
                            ->label('Logo')
                            ->disk('public')
                            ->directory('status-page-logos')
                            ->maxSize(1024)
                            ->helperText('Upload a logo for your status page (max 1MB)'),
                        TextInput::make('website_url')
                            ->url()
                            ->maxLength(255)
                            ->helperText('Link to your main website'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('URL')
                    ->prefix(url('s').'/')
                    ->copyable()
                    ->copyableState(fn ($record) => url('s/'.$record->slug))
                    ->iconPosition(IconPosition::After)
                    ->icon('heroicon-o-link')
                    ->tooltip('Click to copy')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_enabled')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_enabled')
                    ->label('Status')
                    ->placeholder('All Status Pages')
                    ->trueLabel('Enabled Pages')
                    ->falseLabel('Disabled Pages'),
            ])
            ->emptyStateHeading(heading: 'Create your first public status page')
            ->emptyStateDescription('Set up a status page to keep your users informed. Share your public link with your users, or embed the widget on your website.')
            ->emptyStateIcon('heroicon-o-eye')
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Create status page')
                    ->icon('heroicon-o-plus'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('embed')
                    ->label('Embed')
                    ->modalHeading('Embed Status Page')
                    ->modalDescription('Copy and paste this code into your website to embed the status page.')
                    ->modalContent(fn ($record) => view('filament.resources.status-page.embed-code', [
                        'statusPage' => $record,
                    ]))
                    ->modalWidth('2xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->icon('heroicon-o-window'),
                Action::make('open_page')
                    ->label('Open page')
                    ->url(fn ($record) => route('status-page.show', $record->slug))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-link'),

            ])
            ->toolbarActions([

            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
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

    public static function getNavigationLabel(): string
    {
        return 'Status Pages';
    }

    public static function getActions(): array
    {
        return [

        ];
    }
}
