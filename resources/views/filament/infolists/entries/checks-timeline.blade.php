@php
    $checks = $getRecord()->checks()
        ->orderBy('checked_at', 'desc')
        ->limit(20)
        ->get();
@endphp

@if($checks->isEmpty())
    <div class="text-sm text-gray-500 dark:text-gray-400 py-4">
        No checks recorded for this anomaly.
    </div>
@else
    <div class="space-y-3">
        @foreach($checks as $check)
            <div class="flex items-start gap-4 p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700">
                {{-- Status indicator --}}
                <div class="flex-shrink-0 mt-0.5">
                    @if($check->status->value === 'ok')
                        <div class="w-3 h-3 rounded-full bg-success-500"></div>
                    @elseif($check->status->value === 'fail')
                        <div class="w-3 h-3 rounded-full bg-danger-500"></div>
                    @else
                        <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                    @endif
                </div>

                {{-- Check details --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                            @if($check->status->value === 'ok') bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-300
                            @elseif($check->status->value === 'fail') bg-danger-100 text-danger-800 dark:bg-danger-900 dark:text-danger-300
                            @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                            @endif">
                            {{ strtoupper($check->status->value) }}
                        </span>

                        @if($check->response_time)
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $check->response_time }}ms
                            </span>
                        @endif

                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            {{ $check->checked_at->format('M j, g:i:s A') }}
                        </span>
                        
                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            ({{ $check->checked_at->diffForHumans() }})
                        </span>
                    </div>

                    @if($check->output)
                        <div class="mt-2 text-sm text-gray-600 dark:text-gray-300 font-mono bg-gray-100 dark:bg-gray-900 rounded px-2 py-1 overflow-x-auto">
                            {{ Str::limit($check->output, 200) }}
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if($getRecord()->checks()->count() > 20)
        <div class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">
            Showing latest 20 of {{ $getRecord()->checks()->count() }} checks
        </div>
    @endif
@endif
