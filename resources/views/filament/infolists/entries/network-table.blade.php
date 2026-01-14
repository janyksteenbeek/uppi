@php
    $latest = $server->latestMetric();
    $activeInterfaces = $latest?->networkMetrics
        ?->filter(fn ($net) => $net->rx_bytes > 0 || $net->tx_bytes > 0)
        ->sortByDesc(fn ($net) => $net->rx_bytes + $net->tx_bytes)
        ->take(6) ?? collect();
    $inactiveCount = ($latest?->networkMetrics?->count() ?? 0) - $activeInterfaces->count();
@endphp

<div class="w-full">
    @forelse($activeInterfaces as $network)
        <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-700 last:border-0">
            <span class="font-mono text-xs font-medium">{{ $network->interface_name }}</span>
            <div class="flex gap-3 text-xs">
                <span class="text-green-600 dark:text-green-400">↓{{ $network->formatted_rx_bytes }}</span>
                <span class="text-blue-600 dark:text-blue-400">↑{{ $network->formatted_tx_bytes }}</span>
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-400 text-center py-4">No active interfaces</p>
    @endforelse
    
    @if($inactiveCount > 0)
        <p class="text-xs text-gray-400 mt-2 text-center">{{ $inactiveCount }} hidden</p>
    @endif
</div>
