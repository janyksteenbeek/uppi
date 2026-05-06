<?php

namespace App\Services\ErrorTracking\StackTrace\Renderers;

use App\Services\ErrorTracking\StackTrace\StackTraceRenderer;
use Illuminate\Contracts\View\View;

class PhpLaravelRenderer implements StackTraceRenderer
{
    public function supports(string $platform): bool
    {
        return in_array($platform, ['php', 'laravel'], true);
    }

    public function render(array $frames, ?array $exception = null): View
    {
        $normalized = array_values(array_map(fn ($frame) => $this->normalizeFrame($frame), $frames));
        $ordered = array_reverse($normalized);

        $inAppCount = count(array_filter($ordered, fn ($frame) => $frame['in_app']));
        $vendorCount = count($ordered) - $inAppCount;

        return view('filament.error-tracking.stacktrace', [
            'frames' => $ordered,
            'exception' => $exception,
            'in_app_count' => $inAppCount,
            'vendor_count' => $vendorCount,
            'platform_label' => 'PHP',
        ]);
    }

    /**
     * @param  array<string, mixed>  $frame
     * @return array<string, mixed>
     */
    private function normalizeFrame(array $frame): array
    {
        $filename = (string) ($frame['filename'] ?? $frame['abs_path'] ?? '');
        $function = (string) ($frame['function'] ?? '');
        $lineno = isset($frame['lineno']) ? (int) $frame['lineno'] : null;

        $inApp = $frame['in_app'] ?? null;
        if ($inApp === null) {
            $inApp = $filename !== ''
                && ! str_contains($filename, '/vendor/')
                && ! str_contains($filename, '\\vendor\\');
        }

        return [
            'filename' => $filename,
            'short_filename' => $this->shortFilename($filename),
            'function' => $function,
            'lineno' => $lineno,
            'in_app' => (bool) $inApp,
            'context_line' => isset($frame['context_line']) ? (string) $frame['context_line'] : null,
            'pre_context' => array_values((array) ($frame['pre_context'] ?? [])),
            'post_context' => array_values((array) ($frame['post_context'] ?? [])),
            'vars' => is_array($frame['vars'] ?? null) ? $frame['vars'] : null,
        ];
    }

    private function shortFilename(string $filename): string
    {
        if ($filename === '') {
            return '';
        }

        foreach (['/app/', '/vendor/', '/bootstrap/', '/config/', '/database/', '/routes/'] as $marker) {
            $pos = strpos($filename, $marker);
            if ($pos !== false) {
                return ltrim(substr($filename, $pos), '/');
            }
        }

        return $filename;
    }
}
