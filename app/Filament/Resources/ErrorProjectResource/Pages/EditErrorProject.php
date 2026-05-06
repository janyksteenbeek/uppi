<?php

namespace App\Filament\Resources\ErrorProjectResource\Pages;

use App\Filament\Resources\ErrorProjectResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditErrorProject extends EditRecord
{
    protected static string $resource = ErrorProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
