<?php

namespace App\Filament\Resources\ErrorIssueAnomalyRuleResource\Pages;

use App\Filament\Resources\ErrorIssueAnomalyRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListErrorIssueAnomalyRules extends ListRecords
{
    protected static string $resource = ErrorIssueAnomalyRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
