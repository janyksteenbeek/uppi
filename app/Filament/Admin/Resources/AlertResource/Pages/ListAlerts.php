<?php

namespace App\Filament\Admin\Resources\AlertResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Admin\Resources\AlertResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAlerts extends ListRecords
{
    protected static string $resource = AlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
