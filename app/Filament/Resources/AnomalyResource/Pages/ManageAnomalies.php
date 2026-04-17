<?php

namespace App\Filament\Resources\AnomalyResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\AnomalyResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAnomalies extends ManageRecords
{
    protected static string $resource = AnomalyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
