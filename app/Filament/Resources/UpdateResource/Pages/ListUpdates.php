<?php

namespace App\Filament\Resources\UpdateResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\UpdateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUpdates extends ListRecords
{
    protected static string $resource = UpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
