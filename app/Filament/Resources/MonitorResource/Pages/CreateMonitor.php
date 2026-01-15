<?php

namespace App\Filament\Resources\MonitorResource\Pages;

use App\Enums\Monitors\MonitorType;
use App\Filament\Resources\MonitorResource;
use App\Models\Server;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateMonitor extends CreateRecord
{
    protected static string $resource = MonitorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // For server monitors, verify the user owns the server
        if (isset($data['type']) && $data['type'] === MonitorType::SERVER->value && isset($data['address'])) {
            $server = Server::withoutGlobalScopes()->find($data['address']);

            if (! $server || $server->user_id !== auth()->id()) {
                throw ValidationException::withMessages([
                    'address' => 'You do not have access to this server.',
                ]);
            }
        }

        return $data;
    }
}
