<?php

namespace App\Filament\Resources\AnomalyResource\Pages;

use App\Filament\Resources\AnomalyResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAnomaly extends ViewRecord
{
    protected static string $resource = AnomalyResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
