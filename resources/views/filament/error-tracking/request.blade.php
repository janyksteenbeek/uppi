@php
    /** @var array|null $request */
    $request = is_array($request ?? null) ? $request : null;
@endphp

@if (! $request)
    <p class="text-sm text-gray-500 dark:text-gray-400">No request data available.</p>
@else
    <div class="space-y-3 text-sm">
        @if (! empty($request['url']) || ! empty($request['method']))
            <div class="flex flex-wrap items-baseline gap-2">
                @if (! empty($request['method']))
                    <span class="inline-flex items-center rounded bg-primary-100 px-1.5 py-0.5 text-xs font-semibold text-primary-700 dark:bg-primary-900/40 dark:text-primary-200">
                        {{ strtoupper($request['method']) }}
                    </span>
                @endif
                @if (! empty($request['url']))
                    <span class="font-mono text-xs text-gray-700 dark:text-gray-200 break-all">{{ $request['url'] }}</span>
                @endif
            </div>
        @endif

        @if (! empty($request['query_string']))
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Query string</p>
                <pre class="mt-1 max-h-40 overflow-auto rounded bg-gray-900 p-2 text-xs text-gray-100">{{ is_string($request['query_string']) ? $request['query_string'] : json_encode($request['query_string'], JSON_PRETTY_PRINT) }}</pre>
            </div>
        @endif

        @if (! empty($request['headers']))
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Headers</p>
                <pre class="mt-1 max-h-60 overflow-auto rounded bg-gray-900 p-2 text-xs text-gray-100">{{ json_encode($request['headers'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        @endif

        @if (! empty($request['data']))
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Body</p>
                <pre class="mt-1 max-h-60 overflow-auto rounded bg-gray-900 p-2 text-xs text-gray-100">{{ is_string($request['data']) ? $request['data'] : json_encode($request['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        @endif
    </div>
@endif
