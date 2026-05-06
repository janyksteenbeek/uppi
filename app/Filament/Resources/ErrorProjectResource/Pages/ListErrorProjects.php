<?php

namespace App\Filament\Resources\ErrorProjectResource\Pages;

use App\Filament\Resources\ErrorProjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListErrorProjects extends ListRecords
{
    protected static string $resource = ErrorProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
