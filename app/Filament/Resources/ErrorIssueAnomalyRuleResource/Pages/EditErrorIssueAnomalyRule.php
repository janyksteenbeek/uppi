<?php

namespace App\Filament\Resources\ErrorIssueAnomalyRuleResource\Pages;

use App\Filament\Resources\ErrorIssueAnomalyRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditErrorIssueAnomalyRule extends EditRecord
{
    protected static string $resource = ErrorIssueAnomalyRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
