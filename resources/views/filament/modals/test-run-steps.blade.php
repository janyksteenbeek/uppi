<div class="space-y-4">
    {{-- Run Summary --}}
    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                @php
                    $statusColors = [
                        'pending' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                        'running' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                        'success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                        'failure' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                    ];
                    $statusIcons = [
                        'pending' => 'heroicon-o-clock',
                        'running' => 'heroicon-o-arrow-path',
                        'success' => 'heroicon-o-check-circle',
                        'failure' => 'heroicon-o-x-circle',
                    ];
                @endphp
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium {{ $statusColors[$run->status->value] }}">
                    <x-dynamic-component :component="$statusIcons[$run->status->value]" class="h-4 w-4" />
                    {{ $run->status->getLabel() }}
                </span>
                @if($run->duration_ms)
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ number_format($run->duration_ms / 1000, 2) }}s total
                    </span>
                @endif
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                {{ $run->started_at?->format('M j, Y g:i:s a') }}
            </div>
        </div>
    </div>

    {{-- Steps Timeline --}}
    <div class="relative">
        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>
        
        <div class="space-y-4">
            {{-- Entrypoint --}}
            <div class="relative flex items-start gap-4 pl-10">
                <div class="absolute left-2 flex h-5 w-5 items-center justify-center rounded-full bg-blue-500 ring-4 ring-white dark:ring-gray-900">
                    <x-heroicon-s-globe-alt class="h-3 w-3 text-white" />
                </div>
                <div class="flex-1 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-gray-900 dark:text-white">Visit entrypoint</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Start</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-mono">
                        {{ $run->test->entrypoint_url }}
                    </p>
                </div>
            </div>

            {{-- Steps --}}
            @foreach($run->runSteps->sortBy('sort_order') as $runStep)
                @php
                    $step = $runStep->testStep;
                    $isSuccess = $runStep->status === \App\Enums\Tests\TestStatus::SUCCESS;
                    $isFailure = $runStep->status === \App\Enums\Tests\TestStatus::FAILURE;
                    $isPending = $runStep->status === \App\Enums\Tests\TestStatus::PENDING;
                    $isRunning = $runStep->status === \App\Enums\Tests\TestStatus::RUNNING;
                    
                    $dotColor = match(true) {
                        $isSuccess => 'bg-green-500',
                        $isFailure => 'bg-red-500',
                        $isRunning => 'bg-blue-500',
                        default => 'bg-gray-300 dark:bg-gray-600',
                    };
                    
                    $borderColor = match(true) {
                        $isFailure => 'border-red-300 dark:border-red-800',
                        default => 'border-gray-200 dark:border-gray-700',
                    };
                @endphp
                
                <div class="relative flex items-start gap-4 pl-10">
                    <div class="absolute left-2 flex h-5 w-5 items-center justify-center rounded-full {{ $dotColor }} ring-4 ring-white dark:ring-gray-900">
                        @if($isSuccess)
                            <x-heroicon-s-check class="h-3 w-3 text-white" />
                        @elseif($isFailure)
                            <x-heroicon-s-x-mark class="h-3 w-3 text-white" />
                        @elseif($isRunning)
                            <x-heroicon-s-arrow-path class="h-3 w-3 text-white animate-spin" />
                        @else
                            <span class="h-2 w-2 rounded-full bg-white"></span>
                        @endif
                    </div>
                    
                    <div class="flex-1 rounded-lg border {{ $borderColor }} bg-white dark:bg-gray-800 p-3 {{ $isFailure ? 'ring-1 ring-red-500' : '' }}">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <x-dynamic-component :component="$step->type->getIcon()" class="h-4 w-4 text-gray-400" />
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ $step->type->getLabel() }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($runStep->duration_ms)
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ number_format($runStep->duration_ms) }}ms
                                    </span>
                                @endif
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColors[$runStep->status->value] }}">
                                    {{ $runStep->status->getLabel() }}
                                </span>
                            </div>
                        </div>
                        
                        @if($step->value || $step->selector)
                            <div class="mt-2 flex flex-wrap gap-2">
                                @if($step->value)
                                    <code class="rounded bg-gray-100 dark:bg-gray-700 px-2 py-1 text-xs text-gray-700 dark:text-gray-300">
                                        {{ $step->value }}
                                    </code>
                                @endif
                                @if($step->selector)
                                    <code class="rounded bg-purple-100 dark:bg-purple-900 px-2 py-1 text-xs text-purple-700 dark:text-purple-300">
                                        {{ $step->selector }}
                                    </code>
                                @endif
                            </div>
                        @endif
                        
                        @if($isFailure && $runStep->error_message)
                            <div class="mt-3 rounded-md bg-red-50 dark:bg-red-900/20 p-3">
                                <p class="text-sm font-medium text-red-800 dark:text-red-300">Error:</p>
                                <p class="mt-1 text-sm text-red-700 dark:text-red-400 font-mono">
                                    {{ $runStep->error_message }}
                                </p>
                            </div>
                        @endif
                        
                        @if($runStep->screenshot_path)
                            @php
                                $screenshotUrl = URL::signedRoute('test-screenshot', ['testRunStep' => $runStep->id]);
                            @endphp
                            <div class="mt-3">
                                <a href="{{ $screenshotUrl }}" target="_blank" class="block">
                                    <img 
                                        src="{{ $screenshotUrl }}" 
                                        alt="Screenshot" 
                                        class="rounded-lg border border-gray-200 dark:border-gray-700 max-w-full h-auto max-h-64 object-contain hover:opacity-90 transition-opacity"
                                    />
                                </a>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Click to view full size</p>
                            </div>
                        @endif
                        
                        @if($isFailure && $runStep->html_snapshot)
                            <details class="mt-3">
                                <summary class="cursor-pointer text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                                    View HTML snapshot
                                </summary>
                                <div class="mt-2 max-h-48 overflow-x-auto overflow-y-auto rounded-md bg-gray-900 p-3">
                                    <pre class="text-xs text-gray-300 whitespace-pre-wrap break-all max-w-full">{{ \Str::limit($runStep->html_snapshot, 5000) }}</pre>
                                </div>
                            </details>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
