<?php

namespace App\Filament\Admin\Resources\AlertResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Admin\Resources\AlertResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAlert extends EditRecord
{
    protected static string $resource = AlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
