<?php

namespace App\Filament\Resources\ErrorIssueResource\Pages;

use App\Filament\Resources\ErrorIssueResource;
use Filament\Resources\Pages\ListRecords;

class ListErrorIssues extends ListRecords
{
    protected static string $resource = ErrorIssueResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
