<?php

namespace App\Filament\Resources\StatusPageResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\StatusPageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStatusPages extends ListRecords
{
    protected static string $resource = StatusPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
