<?php

namespace App\Filament\Resources\TestResource\Widgets;

use Filament\Widgets\Widget;

class FeatureNotEnabledWidget extends Widget
{
    protected static string $view = 'filament.resources.test-resource.widgets.feature-not-enabled';

    protected int | string | array $columnSpan = 'full';
}
