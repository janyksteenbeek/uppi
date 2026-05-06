<?php

use App\Providers\AppServiceProvider;
use App\Providers\ErrorTrackingServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\MainPanelProvider;
use App\Providers\HorizonServiceProvider;

return [
    AppServiceProvider::class,
    ErrorTrackingServiceProvider::class,
    AdminPanelProvider::class,
    MainPanelProvider::class,
    HorizonServiceProvider::class,
];
