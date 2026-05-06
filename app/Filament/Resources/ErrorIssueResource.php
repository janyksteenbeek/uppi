<?php

namespace App\Filament\Resources;

use App\Enums\ErrorTracking\IssueLevel;
use App\Enums\ErrorTracking\IssueStatus;
use App\Filament\Resources\ErrorIssueResource\Pages;
use App\Models\ErrorTracking\Issue;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ErrorIssueResource extends Resource
{
    protected static ?string $model = Issue::class;

    protected static string|\BackedEnum|null $navigationIcon = 'phosphor-bug';

    protected static ?string $navigationLabel = 'Issues';

    protected static string|\UnitEnum|null $navigationGroup = 'Error Tracking';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                ViewEntry::make('issue_detail')
                    ->view('filament.error-tracking.issue-detail')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['project', 'latestEvent']))
            ->columns([
                ViewColumn::make('issue_cell')
                    ->label('Issue')
                    ->view('filament.error-tracking.columns.issue-cell')
                    ->searchable(['title', 'culprit'])
                    ->extraAttributes(['style' => 'min-width: 360px']),
                TextColumn::make('last_seen_at')
                    ->label('Last seen')
                    ->since()
                    ->sortable()
                    ->color('gray'),
                TextColumn::make('first_seen_at')
                    ->label('Age')
                    ->state(fn (Issue $record) => $record->first_seen_at?->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE, short: true) ?? '—')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(),
                ViewColumn::make('trend_sparkline')
                    ->label('24h')
                    ->view('filament.error-tracking.columns.trend-sparkline'),
                TextColumn::make('times_seen')
                    ->label('Events')
                    ->sortable()
                    ->state(fn (Issue $record) => self::formatCompactNumber($record->times_seen))
                    ->weight('semibold'),
                TextColumn::make('users_seen')
                    ->label('Users')
                    ->sortable()
                    ->state(fn (Issue $record) => self::formatCompactNumber($record->users_seen))
                    ->color('gray')
                    ->toggleable(),
            ])
            ->defaultSort('last_seen_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(IssueStatus::class),
                SelectFilter::make('level')
                    ->options(IssueLevel::class),
                SelectFilter::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('resolve')
                    ->label('Resolve')
                    ->icon('phosphor-check-circle')
                    ->color('success')
                    ->visible(fn (Issue $record) => $record->status !== IssueStatus::RESOLVED)
                    ->action(function (Issue $record) {
                        $record->status = IssueStatus::RESOLVED;
                        $record->resolved_at = now();
                        $record->save();
                    })
                    ->requiresConfirmation(),
                Action::make('ignore')
                    ->label('Ignore')
                    ->icon('phosphor-eye-slash')
                    ->color('gray')
                    ->visible(fn (Issue $record) => $record->status !== IssueStatus::IGNORED)
                    ->action(function (Issue $record) {
                        $record->status = IssueStatus::IGNORED;
                        $record->save();
                    })
                    ->requiresConfirmation(),
                Action::make('reopen')
                    ->label('Reopen')
                    ->icon('phosphor-arrow-clockwise')
                    ->color('warning')
                    ->visible(fn (Issue $record) => $record->status !== IssueStatus::OPEN)
                    ->action(function (Issue $record) {
                        $record->status = IssueStatus::OPEN;
                        $record->resolved_at = null;
                        $record->save();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->striped()
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListErrorIssues::route('/'),
            'view' => Pages\ViewErrorIssue::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'culprit'];
    }

    private static function formatCompactNumber(?int $number): string
    {
        $number = (int) $number;

        if ($number >= 1_000_000) {
            return rtrim(rtrim(number_format($number / 1_000_000, 1), '0'), '.').'M';
        }

        if ($number >= 1_000) {
            return rtrim(rtrim(number_format($number / 1_000, 1), '0'), '.').'K';
        }

        return (string) $number;
    }
}
