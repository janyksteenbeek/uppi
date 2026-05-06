<?php

use App\Http\Controllers\Api\SentryIngestController;
use Illuminate\Support\Facades\Route;

Route::post('/api/{projectId}/envelope', [SentryIngestController::class, 'envelope'])
    ->where('projectId', '[0-9]+')
    ->name('error-tracking.envelope');

Route::post('/api/{projectId}/store', [SentryIngestController::class, 'store'])
    ->where('projectId', '[0-9]+')
    ->name('error-tracking.store');

Route::match(['get', 'post'], '/api/{projectId}/security', [SentryIngestController::class, 'security'])
    ->where('projectId', '[0-9]+')
    ->name('error-tracking.security');
