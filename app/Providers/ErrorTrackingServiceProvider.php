<?php

namespace App\Providers;

use App\Services\ErrorTracking\StackTrace\Renderers\GenericRenderer;
use App\Services\ErrorTracking\StackTrace\Renderers\PhpLaravelRenderer;
use App\Services\ErrorTracking\StackTrace\StackTraceRendererManager;
use Illuminate\Support\ServiceProvider;

class ErrorTrackingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/error-tracking.php', 'error-tracking');

        $this->app->singleton(StackTraceRendererManager::class, function () {
            return new StackTraceRendererManager(
                renderers: [
                    new PhpLaravelRenderer,
                ],
                fallback: new GenericRenderer,
            );
        });
    }
}
