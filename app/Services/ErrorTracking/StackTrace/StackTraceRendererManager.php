<?php

namespace App\Services\ErrorTracking\StackTrace;

class StackTraceRendererManager
{
    /**
     * @param  array<int, StackTraceRenderer>  $renderers
     */
    public function __construct(
        protected array $renderers,
        protected StackTraceRenderer $fallback,
    ) {}

    public function resolveFor(?string $platform): StackTraceRenderer
    {
        $platform = strtolower((string) $platform);

        foreach ($this->renderers as $renderer) {
            if ($renderer->supports($platform)) {
                return $renderer;
            }
        }

        return $this->fallback;
    }

    public function register(StackTraceRenderer $renderer): void
    {
        $this->renderers[] = $renderer;
    }
}
