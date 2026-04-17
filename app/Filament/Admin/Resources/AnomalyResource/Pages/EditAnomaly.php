<?php

namespace App\Filament\Admin\Resources\AnomalyResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Admin\Resources\AnomalyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAnomaly extends EditRecord
{
    protected static string $resource = AnomalyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
