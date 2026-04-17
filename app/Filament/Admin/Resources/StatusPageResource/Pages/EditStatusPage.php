<?php

namespace App\Filament\Admin\Resources\StatusPageResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Admin\Resources\StatusPageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStatusPage extends EditRecord
{
    protected static string $resource = StatusPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
