@php
    /** @var array|null $tags */
    /** @var array|null $contexts */
    /** @var array|null $extra */
    $tags = is_array($tags ?? null) ? $tags : [];
    $contexts = is_array($contexts ?? null) ? $contexts : [];
    $extra = is_array($extra ?? null) ? $extra : [];
@endphp

<div class="space-y-4">
    @if (count($tags) > 0)
        <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tags</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($tags as $key => $value)
                    <span class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-1 text-xs dark:bg-white/10">
                        <span class="font-medium text-gray-500 dark:text-gray-400">{{ $key }}</span>
                        <span class="font-mono text-gray-800 dark:text-gray-100">{{ is_scalar($value) ? $value : json_encode($value) }}</span>
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    @if (count($contexts) > 0)
        <div class="grid gap-3 md:grid-cols-2">
            @foreach ($contexts as $name => $context)
                @if (is_array($context))
                    <div class="rounded-md border border-gray-200 p-3 dark:border-white/10">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $name }}</p>
                        <dl class="mt-2 space-y-1 text-xs">
                            @foreach ($context as $key => $value)
                                @if ($key === 'type')
                                    @continue
                                @endif
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-500 dark:text-gray-400">{{ $key }}</dt>
                                    <dd class="font-mono text-gray-800 dark:text-gray-100 truncate" title="{{ is_scalar($value) ? $value : '' }}">
                                        {{ is_scalar($value) ? $value : json_encode($value) }}
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    @if (count($extra) > 0)
        <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Extra</p>
            <pre class="max-h-60 overflow-auto rounded bg-gray-900 p-2 text-xs text-gray-100">{{ json_encode($extra, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    @endif

    @if (count($tags) === 0 && count($contexts) === 0 && count($extra) === 0)
        <p class="text-sm text-gray-500 dark:text-gray-400">No tags or context data available.</p>
    @endif
</div>
