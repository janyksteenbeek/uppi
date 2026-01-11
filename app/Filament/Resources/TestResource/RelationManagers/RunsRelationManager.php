<?php

namespace App\Filament\Resources\TestResource\RelationManagers;

use App\Enums\Tests\TestStatus;
use App\Models\TestRun;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class RunsRelationManager extends RelationManager
{
    protected static string $relationship = 'runs';

    protected static ?string $title = 'Test runs';

    protected static ?string $icon = 'heroicon-o-play';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('steps_summary')
                    ->label('Steps')
                    ->state(function (TestRun $record): string {
                        $total = $record->runSteps->count();
                        $success = $record->runSteps->where('status', TestStatus::SUCCESS)->count();
                        $failed = $record->runSteps->where('status', TestStatus::FAILURE)->count();

                        if ($total === 0) {
                            return '-';
                        }

                        if ($failed > 0) {
                            return "{$success}/{$total} passed, {$failed} failed";
                        }

                        return "{$success}/{$total} passed";
                    })
                    ->color(fn (TestRun $record) => $record->runSteps->where('status', TestStatus::FAILURE)->count() > 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('duration_ms')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1000, 2) . 's' : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Started')
                    ->since()
                    ->tooltip(fn (TestRun $record) => $record->started_at?->format('j F Y, g:i:s a'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('finished_at')
                    ->label('Finished')
                    ->since()
                    ->tooltip(fn (TestRun $record) => $record->finished_at?->format('j F Y, g:i:s a'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(TestStatus::options()),
            ])
            ->actions([
                Tables\Actions\Action::make('view_steps')
                    ->label('View steps')
                    ->icon('heroicon-o-list-bullet')
                    ->modalHeading(fn (TestRun $record) => 'Run #' . substr($record->id, -8) . ' - Steps')
                    ->modalContent(fn (TestRun $record) => view('filament.modals.test-run-steps', [
                        'run' => $record->load('runSteps.testStep'),
                    ]))
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->emptyStateHeading('No test runs yet')
            ->emptyStateDescription('Runs will appear here after the monitor executes this test.')
            ->emptyStateIcon('heroicon-o-play');
    }
}
