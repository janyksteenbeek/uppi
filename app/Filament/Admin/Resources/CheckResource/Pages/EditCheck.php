<?php

namespace App\Filament\Admin\Resources\CheckResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Admin\Resources\CheckResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCheck extends EditRecord
{
    protected static string $resource = CheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
