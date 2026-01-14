<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CreateServer extends CreateRecord
{
    protected static string $resource = ServerResource::class;

    public function mount(): void
    {
        if (! Auth::user()->hasFeature('server-monitoring')) {
            abort(Response::HTTP_FORBIDDEN);
        }

        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Server created - follow the instructions to install the agent';
    }
}
