<?php

namespace App\Filament\Resources\ErrorIssueResource\Pages;

use App\Enums\ErrorTracking\IssueStatus;
use App\Filament\Resources\ErrorIssueResource;
use App\Models\ErrorTracking\Issue;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewErrorIssue extends ViewRecord
{
    protected static string $resource = ErrorIssueResource::class;

    public function getTitle(): string
    {
        /** @var Issue $record */
        $record = $this->getRecord();

        return $record->title;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resolve')
                ->label('Resolve')
                ->icon('phosphor-check-circle')
                ->color('success')
                ->visible(fn () => $this->getRecord()->status !== IssueStatus::RESOLVED)
                ->action(function () {
                    $record = $this->getRecord();
                    $record->status = IssueStatus::RESOLVED;
                    $record->resolved_at = now();
                    $record->save();
                    $this->refreshFormData(['status', 'resolved_at']);
                }),
            Action::make('ignore')
                ->label('Ignore')
                ->icon('phosphor-eye-slash')
                ->color('gray')
                ->visible(fn () => $this->getRecord()->status !== IssueStatus::IGNORED)
                ->action(function () {
                    $record = $this->getRecord();
                    $record->status = IssueStatus::IGNORED;
                    $record->save();
                    $this->refreshFormData(['status']);
                }),
            Action::make('reopen')
                ->label('Reopen')
                ->icon('phosphor-arrow-clockwise')
                ->color('warning')
                ->visible(fn () => $this->getRecord()->status !== IssueStatus::OPEN)
                ->action(function () {
                    $record = $this->getRecord();
                    $record->status = IssueStatus::OPEN;
                    $record->resolved_at = null;
                    $record->save();
                    $this->refreshFormData(['status', 'resolved_at']);
                }),
        ];
    }
}
