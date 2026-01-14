@php
    $latest = $server->latestMetric();
    $disks = $latest?->diskMetrics?->sortByDesc('usage_percent')->take(6) ?? collect();
    $hiddenCount = ($latest?->diskMetrics?->count() ?? 0) - $disks->count();
@endphp

<div class="w-full">
    @forelse($disks as $disk)
        @php
            $percent = $disk->usage_percent;
            $barColor = match(true) {
                $percent > 90 => 'bg-red-500',
                $percent > 75 => 'bg-yellow-500',
                default => 'bg-green-500',
            };
            $mountDisplay = strlen($disk->mount_point) > 25 
                ? '...' . substr($disk->mount_point, -22) 
                : $disk->mount_point;
        @endphp
        <div class="py-1">
            <div class="flex justify-between items-center mb-1">
                <span class="font-mono text-xs truncate" title="{{ $disk->mount_point }}">{{ $mountDisplay }}</span>
                <span class="text-xs text-gray-500 ml-2 whitespace-nowrap">{{ $disk->formatted_used }} / {{ $disk->formatted_total }}</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                <div class="{{ $barColor }} h-1.5 rounded-full" style="width: {{ min($percent, 100) }}%"></div>
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-400 text-center py-4">No disk data</p>
    @endforelse
    
    @if($hiddenCount > 0)
        <p class="text-xs text-gray-400 mt-2 text-center">+{{ $hiddenCount }} more</p>
    @endif
</div>
