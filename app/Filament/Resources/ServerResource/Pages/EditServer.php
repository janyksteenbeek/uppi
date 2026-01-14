<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EditServer extends EditRecord
{
    protected static string $resource = ServerResource::class;

    public function mount(int|string $record): void
    {
        if (! Auth::user()->hasFeature('server-monitoring')) {
            abort(Response::HTTP_FORBIDDEN);
        }

        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
