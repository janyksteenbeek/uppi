<?php

namespace App\Filament\Resources\MonitorResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\MonitorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMonitors extends ListRecords
{
    protected static string $resource = MonitorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
