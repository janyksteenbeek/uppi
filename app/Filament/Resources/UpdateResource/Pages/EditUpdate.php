<?php

namespace App\Filament\Resources\UpdateResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\UpdateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUpdate extends EditRecord
{
    protected static string $resource = UpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
