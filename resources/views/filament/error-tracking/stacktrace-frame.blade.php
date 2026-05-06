@php
    /** @var array{filename:string, short_filename:string, function:string, lineno:?int, in_app:bool, context_line:?string, pre_context:array, post_context:array, vars:?array} $frame */
    $hasContext = $frame['context_line'] !== null || count($frame['pre_context']) > 0 || count($frame['post_context']) > 0;
    $startLine = max(1, ($frame['lineno'] ?? 1) - count($frame['pre_context']));
@endphp

<div class="border-t border-gray-100 first:border-t-0 dark:border-white/5">
    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-4 py-2 {{ $frame['in_app'] ? 'bg-white dark:bg-gray-900' : 'bg-gray-50 dark:bg-gray-900/50' }}">
        <span class="font-mono text-sm font-medium {{ $frame['in_app'] ? 'text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400' }}">
            {{ $frame['function'] ?: '<anonymous>' }}
        </span>
        @if ($frame['short_filename'])
            <span class="font-mono text-xs text-gray-500 dark:text-gray-400">
                {{ $frame['short_filename'] }}@if ($frame['lineno']):{{ $frame['lineno'] }}@endif
            </span>
        @endif
        @if (! $frame['in_app'])
            <span class="ml-auto inline-flex items-center rounded bg-gray-200/70 px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wide text-gray-600 dark:bg-white/10 dark:text-gray-300">
                vendor
            </span>
        @endif
    </div>

    @if ($hasContext)
        <pre class="overflow-x-auto bg-gray-900 px-4 py-3 text-xs leading-relaxed text-gray-100"><code>@php $line = $startLine; @endphp@foreach ($frame['pre_context'] as $contextLine)<span class="block opacity-60"><span class="mr-3 inline-block w-10 select-none text-right text-gray-500">{{ $line }}</span>{{ $contextLine }}</span>@php $line++; @endphp
@endforeach
@if ($frame['context_line'] !== null)<span class="block bg-danger-500/20"><span class="mr-3 inline-block w-10 select-none text-right text-danger-400">{{ $frame['lineno'] ?? $line }}</span>{{ $frame['context_line'] }}</span>@php $line++; @endphp
@endif
@foreach ($frame['post_context'] as $contextLine)<span class="block opacity-60"><span class="mr-3 inline-block w-10 select-none text-right text-gray-500">{{ $line }}</span>{{ $contextLine }}</span>@php $line++; @endphp
@endforeach</code></pre>
    @endif
</div>
