<?php

namespace App\Filament\Resources\AlertResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\AlertResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAlerts extends ManageRecords
{
    protected static string $resource = AlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
