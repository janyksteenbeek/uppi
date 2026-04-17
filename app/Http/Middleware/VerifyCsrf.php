<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Str;

// @see https://github.com/livewire/livewire/discussions/7729
class VerifyCsrf extends ValidateCsrfToken
{
    protected array $exceptComponents = [
        'status-page.overall-status',
        'status-page.monitor-status',
        'status-page.monitors-list',
        'status-page.updates-list',
        'status-page.show',
    ];

    /**
     * Check if the CSRF tokens match for the given request.
     *
     *
     * @return bool True if the CSRF tokens match, false otherwise.
     */
    protected function tokensMatch(mixed $request): bool
    {
        $componentPath = $this->getLivewireComponentPath($request);

        foreach ($this->exceptComponents as $exceptComponent) {
            if (Str::is($exceptComponent, $componentPath)) {
                return true;
            }
        }

        return parent::tokensMatch($request);
    }

    /**
     * Get Livewire component path from the request.
     */
    protected function getLivewireComponentPath(mixed $request): ?string
    {
        $components = $request->input('components')[0] ?? [];
        $snapshot = json_decode($components['snapshot'] ?? '{}', true);
        $memo = $snapshot['memo'] ?? [];

        return $memo['name'] ?? null;
    }
}
