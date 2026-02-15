<div class="bg-white rounded-2xl shadow-sm border border-neutral-100 p-6" 
    wire:poll.30s 
    id="msts-{{ $item->id }}"
    x-data="{ 
        tooltipVisible: false,
        tooltipDate: '',
        tooltipStatus: '',
        tooltipX: 0,
        tooltipY: 0,
        showTooltip(date, status, event) {
            this.tooltipDate = date;
            this.tooltipStatus = status;
            const rect = event.target.getBoundingClientRect();
            this.tooltipX = rect.left + (rect.width/2) - 30;
            this.tooltipY = rect.top - 60;
            this.tooltipVisible = true;
        },
        hideTooltip() {
            this.tooltipVisible = false;
        }
    }">
    
    <div class="flex items-center gap-3 mb-6">
        @if($item->is_showing_favicon && $item->is_enabled)
            <img src="{{ URL::signedRoute('icon', ['statusPageItem' => $item]) }}"
                 alt="{{ $item->name }}"
                 class="w-6 h-6 object-contain">
        @endif
        <p class="font-medium text-neutral-900 text-base">{{ $item->name }}</p>
        <div class="flex items-center gap-2 ml-auto">
            <span @class([
                'flex items-center justify-center rounded-full w-3 h-3',
                'bg-green-600' => $item->monitor->status === \App\Enums\Checks\Status::OK,
                'bg-red-600' => $item->monitor->status === \App\Enums\Checks\Status::FAIL,
                'bg-yellow-600' => $item->monitor->status === \App\Enums\Checks\Status::UNKNOWN,
            ])></span>
            <span @class([
                'text-sm font-medium',
                'text-green-600' => $item->monitor->status === \App\Enums\Checks\Status::OK,
                'text-red-600' => $item->monitor->status === \App\Enums\Checks\Status::FAIL,
                'text-yellow-600' => $item->monitor->status === \App\Enums\Checks\Status::UNKNOWN,
            ])>{{ $item->monitor->status->label() }}</span>
        </div>
    </div>
    @php
        $regionStatuses = $item->monitor->regionStatuses();
    @endphp
    @if(!empty($regionStatuses))
        <div class="flex flex-wrap items-center gap-2 mb-5">
            @foreach($regionStatuses as $region => $status)
                @php
                    $bg = 'bg-neutral-100 text-neutral-700 border border-neutral-200';
                    $dot = 'bg-neutral-400';
                    if ($status === \App\Enums\Checks\Status::OK) { $bg = 'bg-green-50 text-green-700 border border-green-200'; $dot = 'bg-green-500'; }
                    elseif ($status === \App\Enums\Checks\Status::FAIL) { $bg = 'bg-red-50 text-red-700 border border-red-200'; $dot = 'bg-red-500'; }
                    elseif ($status === \App\Enums\Checks\Status::UNKNOWN) { $bg = 'bg-yellow-50 text-yellow-800 border border-yellow-200'; $dot = 'bg-yellow-500'; }
                @endphp
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium {{ $bg }} shadow-sm">
                    <span class="w-2 h-2 rounded-full {{ $dot }}"></span>
                    <span class="uppercase tracking-wide">{{ $region }}</span>
                    <span class="hidden sm:inline">{{ $status?->label() ?? 'No data' }}</span>
                </span>
            @endforeach
        </div>
    @endif
    <div class="grid grid-flow-col justify-stretch gap-2 relative">
        @php
            $daysCount = count($dates);
            $lastSevenDaysStart = $daysCount - 7;
            $lastFourteenDaysStart = $daysCount - 14;
            $lastThirtyDaysStart = $daysCount - 30;
        @endphp
        
        @foreach($dates as $index => $date)
            <div
                @class([
                    'relative h-8 rounded-lg cursor-pointer',
                    'hidden lg:block' => $index < $lastThirtyDaysStart,
                    'hidden md:block lg:block' => $index < $lastFourteenDaysStart && $index >= $lastThirtyDaysStart,
                    'hidden sm:block md:block lg:block' => $index < $lastSevenDaysStart && $index >= $lastFourteenDaysStart,
                    'bg-green-300 border border-green-400' => $statuses[$index] === true,
                    'bg-red-300 border border-red-400' => $statuses[$index] === false,
                    'bg-neutral-100 border border-neutral-200' => $statuses[$index] === null,
                ])
                @mouseenter="showTooltip('{{ \Carbon\Carbon::parse($date)->format('F j, Y') }}', '{{ $statuses[$index] === true ? '✓ OK' : ($statuses[$index] === false ? '✕ Disruption' : 'No data available') }}', $event)"
                @mouseleave="hideTooltip()"
            >
                <div class="h-full flex items-end">
                    @if($statuses[$index] === true)
                        <div class="w-full h-full bg-green-500 rounded-lg opacity-60"></div>
                    @elseif($statuses[$index] === false)
                        <div class="w-full h-full bg-red-500 rounded-lg opacity-60"></div>
                    @endif
                </div>
            </div>
        @endforeach
        
        <!-- Single shared tooltip -->
        <div 
            x-show="tooltipVisible"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            class="fixed z-50 px-3 py-2 rounded-lg bg-neutral-800 text-white text-xs whitespace-nowrap shadow-lg pointer-events-none"
            :style="`top: ${tooltipY}px; left: ${tooltipX}px;`">
            <div class="font-medium mb-0.5" x-text="tooltipDate"></div>
            <div x-text="tooltipStatus"></div>
        </div>
    </div>
    <div class="mt-3 text-xs text-neutral-500 flex justify-between">
        <span class="sm:hidden">Last 7 days</span>
        <span class="hidden sm:block md:hidden">Last 14 days</span>
        <span class="hidden md:block">Last 30 days</span>
 
        <span class="hidden md:block">{{ \Carbon\Carbon::now()->subDays(29)->format('M j') }} - {{ \Carbon\Carbon::now()->format('M j') }}</span>
        <span class="sm:hidden">{{ \Carbon\Carbon::now()->subDays(6)->format('M j') }} - {{ \Carbon\Carbon::now()->format('M j') }}</span>
        <span class="hidden sm:block md:hidden">{{ \Carbon\Carbon::now()->subDays(13)->format('M j') }} - {{ \Carbon\Carbon::now()->format('M j') }}</span>
    </div>
</div>
