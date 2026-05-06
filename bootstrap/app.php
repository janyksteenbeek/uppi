<?php

use App\Http\Middleware\VerifyCsrf;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::group([], __DIR__.'/../routes/sentry-ingest.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->web(replace: [
            ValidateCsrfToken::class => VerifyCsrf::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'api/*/envelope',
            'api/*/envelope/',
            'api/*/store',
            'api/*/store/',
            'api/*/security',
            'api/*/security/',
        ]);
        $middleware->redirectGuestsTo(fn () => route('filament.main.auth.login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
    })->create();
