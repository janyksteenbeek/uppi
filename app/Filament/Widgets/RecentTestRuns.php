<?php

namespace App\Filament\Widgets;

use App\Enums\Tests\TestStatus;
use App\Models\TestRun;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\View\View;

class RecentTestRuns extends BaseWidget
{
    protected static ?string $heading = 'Recent test runs';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return auth()->user()->hasFeature('run-tests');
    }

    public function placeholder(): View
    {
        return view('filament.widgets.placeholder');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TestRun::query()
                    ->whereHas('test', fn ($q) => $q->where('user_id', auth()->id()))
                    ->with(['test', 'runSteps'])
                    ->orderBy('started_at', 'desc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (TestStatus $state): string => match ($state) {
                        TestStatus::SUCCESS => 'success',
                        TestStatus::FAILURE => 'danger',
                        TestStatus::RUNNING => 'info',
                        TestStatus::PENDING => 'gray',
                    })
                    ->label(''),
                Tables\Columns\TextColumn::make('test.name')
                    ->label('Test')
                    ->searchable()
                    ->url(fn ($record) => $record->test ? route('filament.app.resources.tests.edit', $record->test) : null),
                Tables\Columns\TextColumn::make('steps_progress')
                    ->label('Steps')
                    ->state(function ($record) {
                        $success = $record->runSteps->where('status', TestStatus::SUCCESS)->count();
                        $total = $record->runSteps->count();

                        return "{$success}/{$total}";
                    })
                    ->color(fn ($record) => $record->status === TestStatus::SUCCESS ? 'success' : ($record->status === TestStatus::FAILURE ? 'danger' : 'gray')),
                Tables\Columns\TextColumn::make('duration_ms')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1000, 2) . 's' : '-'),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Started')
                    ->since()
                    ->tooltip(fn ($record) => $record->started_at?->format('M j, Y g:i:s a'))
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn ($record) => 'Test run: ' . $record->test?->name)
                    ->modalContent(fn ($record) => view('filament.modals.test-run-steps', ['run' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->searchable(false)
            ->emptyStateIcon('heroicon-o-beaker')
            ->emptyStateHeading('No test runs yet')
            ->emptyStateDescription('Run a test to see results here')
            ->paginated(false)
            ->defaultSort('started_at', 'desc');
    }
}
