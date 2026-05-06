<?php

namespace App\Services\ErrorTracking\StackTrace\Renderers;

use App\Services\ErrorTracking\StackTrace\StackTraceRenderer;
use Illuminate\Contracts\View\View;

class GenericRenderer implements StackTraceRenderer
{
    public function supports(string $platform): bool
    {
        return true;
    }

    public function render(array $frames, ?array $exception = null): View
    {
        $ordered = array_reverse(array_values($frames));

        return view('filament.error-tracking.stacktrace', [
            'frames' => array_map(fn ($frame) => [
                'filename' => (string) ($frame['filename'] ?? $frame['abs_path'] ?? ''),
                'short_filename' => (string) ($frame['filename'] ?? $frame['abs_path'] ?? ''),
                'function' => (string) ($frame['function'] ?? ''),
                'lineno' => isset($frame['lineno']) ? (int) $frame['lineno'] : null,
                'in_app' => (bool) ($frame['in_app'] ?? false),
                'context_line' => isset($frame['context_line']) ? (string) $frame['context_line'] : null,
                'pre_context' => array_values((array) ($frame['pre_context'] ?? [])),
                'post_context' => array_values((array) ($frame['post_context'] ?? [])),
                'vars' => is_array($frame['vars'] ?? null) ? $frame['vars'] : null,
            ], $ordered),
            'exception' => $exception,
            'in_app_count' => 0,
            'vendor_count' => count($ordered),
            'platform_label' => 'Generic',
        ]);
    }
}
