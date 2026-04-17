<?php

namespace App\Filament\Admin\Resources\CheckResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Admin\Resources\CheckResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChecks extends ListRecords
{
    protected static string $resource = CheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
