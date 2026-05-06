<?php

namespace App\Filament\Resources\ErrorProjectResource\Pages;

use App\Filament\Resources\ErrorProjectResource;
use App\Models\ErrorTracking\Project;
use Filament\Resources\Pages\CreateRecord;

class CreateErrorProject extends CreateRecord
{
    protected static string $resource = ErrorProjectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['internal_id'])) {
            $data['internal_id'] = $this->generateInternalId();
        }

        return $data;
    }

    private function generateInternalId(): int
    {
        do {
            $candidate = random_int(100_000, 9_999_999);
        } while (
            Project::query()
                ->withoutGlobalScope('user')
                ->where('internal_id', $candidate)
                ->exists()
        );

        return $candidate;
    }
}
