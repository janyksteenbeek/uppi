@php
    $latest = $server->latestMetric();
    $checks = collect();
    
    if ($latest) {
        // CPU
        $cpuUsage = $latest->cpu_usage;
        $checks->push([
            'name' => 'CPU',
            'value' => number_format($cpuUsage, 1) . '%',
            'status' => $cpuUsage > 90 ? 'critical' : ($cpuUsage > 75 ? 'warning' : 'healthy'),
        ]);
        
        // Memory
        $memoryPercent = $latest->memory_total > 0 
            ? ($latest->memory_used / $latest->memory_total) * 100 
            : 0;
        $checks->push([
            'name' => 'Memory',
            'value' => number_format($memoryPercent, 1) . '%',
            'status' => $memoryPercent > 90 ? 'critical' : ($memoryPercent > 80 ? 'warning' : 'healthy'),
        ]);
        
        // Swap
        $swapPercent = $latest->swap_total > 0 
            ? ($latest->swap_used / $latest->swap_total) * 100 
            : 0;
        if ($latest->swap_total > 0) {
            $checks->push([
                'name' => 'Swap',
                'value' => number_format($swapPercent, 1) . '%',
                'status' => $swapPercent > 80 ? 'critical' : ($swapPercent > 50 ? 'warning' : 'healthy'),
            ]);
        }
        
        // Disk
        $worstDisk = $latest->diskMetrics->sortByDesc('usage_percent')->first();
        if ($worstDisk) {
            $checks->push([
                'name' => 'Disk',
                'value' => number_format($worstDisk->usage_percent, 1) . '%',
                'status' => $worstDisk->usage_percent > 90 ? 'critical' : ($worstDisk->usage_percent > 80 ? 'warning' : 'healthy'),
            ]);
        }
        
        // Load
        $loadAvg = $latest->load_average_1 ?? 0;
        $checks->push([
            'name' => 'Load',
            'value' => number_format($loadAvg, 2),
            'status' => $loadAvg > 10 ? 'critical' : ($loadAvg > 5 ? 'warning' : 'healthy'),
        ]);
    }
    
    $criticalCount = $checks->where('status', 'critical')->count();
    $warningCount = $checks->where('status', 'warning')->count();
@endphp

<div class="w-full">
    @forelse($checks as $check)
        @php
            $config = match($check['status']) {
                'critical' => ['icon' => '✗', 'color' => 'text-red-500', 'bg' => 'bg-red-50 dark:bg-red-900/20'],
                'warning' => ['icon' => '!', 'color' => 'text-yellow-500', 'bg' => 'bg-yellow-50 dark:bg-yellow-900/20'],
                default => ['icon' => '✓', 'color' => 'text-green-500', 'bg' => 'bg-green-50 dark:bg-green-900/20'],
            };
        @endphp
        <div class="flex items-center justify-between py-1.5 px-2 rounded-lg mb-1 {{ $config['bg'] }}">
            <div class="flex items-center gap-2">
                <span class="{{ $config['color'] }} font-bold text-sm">{{ $config['icon'] }}</span>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $check['name'] }}</span>
            </div>
            <span class="text-xs font-mono {{ $config['color'] }}">{{ $check['value'] }}</span>
        </div>
    @empty
        <p class="text-sm text-gray-400 text-center py-4">No data yet</p>
    @endforelse
    
    @if($criticalCount > 0)
        <p class="text-xs text-red-500 mt-2 text-center font-medium">⚠ {{ $criticalCount }} critical</p>
    @elseif($warningCount > 0)
        <p class="text-xs text-yellow-500 mt-2 text-center font-medium">{{ $warningCount }} warning(s)</p>
    @elseif($checks->isNotEmpty())
        <p class="text-xs text-green-500 mt-2 text-center font-medium">All healthy ✓</p>
    @endif
</div>
