<?php

namespace App\Filament\Resources\StatusPageResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\StatusPageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStatusPage extends EditRecord
{
    protected static string $resource = StatusPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->requiresConfirmation(),
        ];
    }
}
