<?php

use App\Http\Controllers\IconController;
use App\Http\Controllers\PrivacyController;
use App\Http\Controllers\StatusPageController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! config('app.marketing')) {
        return redirect('dashboard');
    }

    if (auth()->check()) {
        return redirect(\App\Filament\Pages\Dashboard::getUrl());
    }

    return view('welcome');
});

Route::get('robots.txt', function () {
    if (! config('app.marketing')) {
        return response()->view('robots.deny')->header('Content-Type', 'text/plain');
    }

    return response()->view('robots.allow')->header('Content-Type', 'text/plain');
});

Route::get('/s/{statusPage:slug}', [StatusPageController::class, 'show'])->name('status-page.show');
Route::get('/s/{statusPage:slug}/status.json', [StatusPageController::class, 'statusJson'])->name('status-page.status-json');
Route::get('/s/{statusPage:slug}/embed', [StatusPageController::class, 'embed'])->name('status-page.embed');

Route::get('icon/{statusPageItem}', IconController::class)->name('icon')->middleware('signed');

Route::get('/embed/{user}/embed.js', function (User $user) {
    return response()
        ->view('js.embed', [
            'user' => $user,
        ])
        ->header('Content-Type', 'application/javascript')
        ->header('Cache-Control', 'public, max-age=3600');
})->name('embed.js');

Route::get('/privacy', PrivacyController::class)->name('privacy');

Route::get('/test-screenshot/{testRunStep}', function (\App\Models\TestRunStep $testRunStep) {
    if (! $testRunStep->screenshot_path || ! \Storage::exists($testRunStep->screenshot_path)) {
        abort(404);
    }

    return \Storage::response($testRunStep->screenshot_path);
})->name('test-screenshot')->middleware('signed');
