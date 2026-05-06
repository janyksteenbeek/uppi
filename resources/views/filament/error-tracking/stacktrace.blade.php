@php
    /** @var array<int, array{filename:string, short_filename:string, function:string, lineno:?int, in_app:bool, context_line:?string, pre_context:array, post_context:array, vars:?array}> $frames */
    /** @var array|null $exception */
    /** @var int $in_app_count */
    /** @var int $vendor_count */
    /** @var string $platform_label */

    $exceptionType = $exception['values'][0]['type'] ?? null;
    $exceptionValue = $exception['values'][0]['value'] ?? null;
@endphp

<div class="space-y-4">
    @if ($exceptionType)
        <div class="rounded-lg border border-danger-200 bg-danger-50 dark:border-danger-700/40 dark:bg-danger-900/20 p-4">
            <div class="flex items-baseline gap-2">
                <span class="inline-flex items-center rounded-md bg-danger-100 dark:bg-danger-700/40 px-2 py-0.5 text-xs font-semibold text-danger-800 dark:text-danger-200">
                    {{ $platform_label }}
                </span>
                <span class="font-mono text-sm font-semibold text-danger-900 dark:text-danger-100">
                    {{ $exceptionType }}
                </span>
            </div>
            @if ($exceptionValue)
                <p class="mt-1 font-mono text-sm text-danger-900 dark:text-danger-100 break-words">
                    {{ $exceptionValue }}
                </p>
            @endif
        </div>
    @endif

    @if (count($frames) === 0)
        <p class="text-sm text-gray-500 dark:text-gray-400">No stack frames available.</p>
    @else
        @php
            $vendorBuffer = [];
            $renderedFrames = [];
            foreach ($frames as $idx => $frame) {
                if ($frame['in_app']) {
                    if ($vendorBuffer !== []) {
                        $renderedFrames[] = ['type' => 'vendor', 'frames' => $vendorBuffer];
                        $vendorBuffer = [];
                    }
                    $renderedFrames[] = ['type' => 'frame', 'frame' => $frame];
                } else {
                    $vendorBuffer[] = $frame;
                }
            }
            if ($vendorBuffer !== []) {
                $renderedFrames[] = ['type' => 'vendor', 'frames' => $vendorBuffer];
            }
        @endphp

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
            @foreach ($renderedFrames as $entry)
                @if ($entry['type'] === 'frame')
                    @include('filament.error-tracking.stacktrace-frame', ['frame' => $entry['frame']])
                @else
                    <div
                        x-data="{ open: false }"
                        class="border-t border-gray-100 first:border-t-0 dark:border-white/5"
                    >
                        <button
                            type="button"
                            @click="open = !open"
                            class="flex w-full items-center justify-between gap-2 px-4 py-2 text-left text-xs font-medium text-gray-500 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-white/5"
                        >
                            <span>
                                <span x-text="open ? 'Hide' : 'Show'"></span>
                                {{ count($entry['frames']) }} vendor frame{{ count($entry['frames']) === 1 ? '' : 's' }}
                            </span>
                            <svg class="h-4 w-4 transition" :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.18l3.71-3.95a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        <div x-show="open" x-cloak>
                            @foreach ($entry['frames'] as $vendorFrame)
                                @include('filament.error-tracking.stacktrace-frame', ['frame' => $vendorFrame])
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ $in_app_count }} app frame{{ $in_app_count === 1 ? '' : 's' }} • {{ $vendor_count }} vendor frame{{ $vendor_count === 1 ? '' : 's' }}
        </p>
    @endif
</div>
