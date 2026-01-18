@php
    $checks = $getRecord()->checks()->latest('checked_at')->limit(20)->get();
@endphp

<div class="space-y-2">
    @forelse($checks as $check)
        <div class="flex items-start gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50">
            <div class="flex-shrink-0 mt-0.5">
                @if($check->status->value === 'ok')
                    @svg('heroicon-s-check-circle', 'w-5 h-5 text-success-500')
                @elseif($check->status->value === 'fail')
                    @svg('heroicon-s-x-circle', 'w-5 h-5 text-danger-500')
                @else
                    @svg('heroicon-s-question-mark-circle', 'w-5 h-5 text-warning-500')
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ $check->status->label() }}
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $check->checked_at->format('M j, g:i:s A') }}
                    </span>
                </div>
                @if($check->response_time)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Response time: {{ $check->response_time }}ms
                    </p>
                @endif
                @if($check->output)
                    <p class="text-xs text-gray-600 dark:text-gray-300 mt-1 font-mono break-all">
                        {{ Str::limit($check->output, 200) }}
                    </p>
                @endif
            </div>
        </div>
    @empty
        <div class="text-center py-6 text-gray-500 dark:text-gray-400">
            @svg('heroicon-o-signal', 'w-8 h-8 mx-auto mb-2 opacity-50')
            <p class="text-sm">No checks recorded for this anomaly</p>
        </div>
    @endforelse
</div>
