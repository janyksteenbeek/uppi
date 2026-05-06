<?php

namespace App\Services\ErrorTracking\StackTrace;

use Illuminate\Contracts\View\View;

interface StackTraceRenderer
{
    public function supports(string $platform): bool;

    /**
     * @param  array<int, array<string, mixed>>  $frames
     * @param  array<string, mixed>|null  $exception
     */
    public function render(array $frames, ?array $exception = null): View;
}
