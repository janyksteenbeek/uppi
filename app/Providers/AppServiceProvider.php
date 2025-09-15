<?php

namespace App\Providers;

use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DateTimePicker::configureUsing(function (DateTimePicker $component): void
        {
            $component->timezone(auth()->user()?->timezone ?? config('app.timezone'));
        });

        TextColumn::configureUsing(function (TextColumn $component): void
        {
            if (str_ends_with($component->getName(), '_at')) {
                $component->timezone(auth()->user()?->timezone ?? config('app.timezone'));
            }
        });
    }
}
