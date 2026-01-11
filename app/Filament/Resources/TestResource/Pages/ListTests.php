<?php

namespace App\Filament\Resources\TestResource\Pages;

use App\Filament\Resources\TestResource;
use App\Filament\Resources\TestResource\Widgets\FeatureNotEnabledWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListTests extends ListRecords
{
    protected static string $resource = TestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        if (! Auth::user()->hasFeature('run-tests')) {
            return [
                FeatureNotEnabledWidget::class,
            ];
        }

        return [];
    }
}
