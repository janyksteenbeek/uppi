<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ListServers extends ListRecords
{
    protected static string $resource = ServerResource::class;

    public function mount(): void
    {
        if (! Auth::user()->hasFeature('server-monitoring')) {
            abort(Response::HTTP_FORBIDDEN);
        }

        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
