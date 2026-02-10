<div
    wire:poll.30s
    x-data="monitoringWall(@js($this->monitorOptions), {{ $this->displayMonitors->where('has_active_anomaly', true)->count() }})"
    x-init="init()"
    class="h-screen w-screen p-4 flex flex-col"
>
    <!-- Settings Button -->
    <div class="absolute top-4 right-4 z-50">
        <button
            @click="settingsOpen = !settingsOpen"
            class="p-3 rounded-full bg-white dark:bg-neutral-800 shadow-lg hover:shadow-xl transition-all duration-200 border border-neutral-200 dark:border-neutral-700"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-neutral-600 dark:text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </button>
    </div>

    <!-- Settings Panel -->
    <div
        x-show="settingsOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-x-4"
        x-transition:enter-end="opacity-100 translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 translate-x-4"
        @click.outside="settingsOpen = false"
        class="absolute top-20 right-4 z-40 w-80 bg-white dark:bg-neutral-800 rounded-2xl shadow-2xl border border-neutral-200 dark:border-neutral-700 overflow-hidden"
    >
        <div class="p-4 border-b border-neutral-200 dark:border-neutral-700">
            <h3 class="text-lg font-semibold text-neutral-900 dark:text-white">settings</h3>
        </div>

        <div class="p-4 space-y-4 max-h-[60vh] overflow-y-auto">
            <!-- Dark Mode Toggle -->
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-neutral-700 dark:text-neutral-300">dark mode</span>
                <button
                    @click="toggleDarkMode()"
                    :class="darkMode ? 'bg-emerald-500' : 'bg-neutral-300 dark:bg-neutral-600'"
                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200"
                >
                    <span
                        :class="darkMode ? 'translate-x-6' : 'translate-x-1'"
                        class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform duration-200"
                    ></span>
                </button>
            </div>

            <!-- Monitor Selection -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-neutral-700 dark:text-neutral-300">monitors</span>
                    <button
                        @click="toggleAllMonitors()"
                        class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline"
                    >
                        <span x-text="allSelected ? 'deselect all' : 'select all'"></span>
                    </button>
                </div>
                <div class="space-y-2">
                    <template x-for="(id, name) in monitors" :key="id">
                        <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-neutral-50 dark:hover:bg-neutral-700/50 cursor-pointer transition-colors">
                            <input
                                type="checkbox"
                                :value="id"
                                x-model="selectedMonitors"
                                @change="saveSelection()"
                                class="w-4 h-4 rounded border-neutral-300 dark:border-neutral-600 text-emerald-500 focus:ring-emerald-500 focus:ring-offset-0 bg-white dark:bg-neutral-700"
                            >
                            <span x-text="name" class="text-sm text-neutral-700 dark:text-neutral-300 truncate"></span>
                        </label>
                    </template>
                </div>
            </div>
        </div>

        <div class="p-4 border-t border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-900/50">
            <p class="text-xs text-neutral-500 dark:text-neutral-400 text-center">
                selection saved locally
            </p>
        </div>
    </div>

    <!-- Monitor Grid -->
    <div
        class="flex-1 grid gap-3 auto-rows-fr grid-flow-dense"
        :style="gridStyle"
    >
        @foreach($this->displayMonitors as $monitor)
            <div
                wire:key="monitor-{{ $monitor->id }}"
                @if($monitor->has_active_anomaly && $monitor->downtime_started_at)
                    x-data="downtimeCounter('{{ $monitor->downtime_started_at }}')"
                @endif
                @class([
                    'rounded-2xl flex flex-col items-center justify-center p-6 transition-all duration-300 relative overflow-hidden',
                    'col-span-2 row-span-2 bg-red-500 shadow-lg shadow-red-500/30' => $monitor->has_active_anomaly,
                    'bg-white dark:bg-neutral-800 border-2 border-emerald-400 dark:border-emerald-500 shadow-lg shadow-emerald-500/10' => !$monitor->has_active_anomaly,
                ])
            >
                @if($monitor->has_active_anomaly)
                    <div class="absolute inset-0 bg-gradient-to-br from-red-400 to-red-600 opacity-90"></div>
                @endif

                <!-- Sparkline Background -->
                @if(count($monitor->response_times) > 1)
                    <div class="absolute inset-0 flex items-end overflow-hidden opacity-30">
                        @php
                            $times = $monitor->response_times;
                            $max = max($times) ?: 1;
                            $min = min($times) ?: 0;
                            $range = $max - $min ?: 1;
                            $count = count($times);
                            $width = 100;
                            $height = 100;

                            $points = [];
                            foreach ($times as $i => $time) {
                                $x = ($i / max(1, $count - 1)) * $width;
                                $y = $height - (($time - $min) / $range) * $height * 0.8 - ($height * 0.1);
                                $points[] = "$x,$y";
                            }

                            $areaPath = "M0,$height L" . implode(' L', $points) . " L$width,$height Z";
                            $linePath = "M" . implode(' L', $points);

                            $strokeColor = $monitor->has_active_anomaly ? 'rgba(255,255,255,0.6)' : 'rgba(16,185,129,0.6)';
                        @endphp
                        <svg
                            viewBox="0 0 {{ $width }} {{ $height }}"
                            preserveAspectRatio="none"
                            class="w-full h-full"
                        >
                            <defs>
                                <linearGradient id="gradient-{{ $monitor->id }}" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" style="stop-color:{{ $strokeColor }};stop-opacity:0.4" />
                                    <stop offset="100%" style="stop-color:{{ $strokeColor }};stop-opacity:0" />
                                </linearGradient>
                            </defs>
                            <path d="{{ $areaPath }}" fill="url(#gradient-{{ $monitor->id }})" />
                            <path d="{{ $linePath }}" fill="none" stroke="{{ $strokeColor }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                        </svg>
                    </div>
                @endif

                <div class="relative z-10 text-center flex-1 flex flex-col justify-center">
                    <!-- Monitor Name -->
                    <h2 @class([
                        'font-bold max-w-full break-words hyphens-auto leading-tight',
                        'text-white' => $monitor->has_active_anomaly,
                        'text-neutral-800 dark:text-white' => !$monitor->has_active_anomaly,
                    ])
                    style="font-size: clamp(0.75rem, 2vw, 2rem);"
                    >
                        {{ $monitor->name }}
                    </h2>

                    <!-- Status -->
                    @if($monitor->has_active_anomaly)
                        <p class="text-red-100 text-base mt-2 uppercase tracking-wide font-semibold">down</p>

                        @if($monitor->downtime_started_at)
                            <p class="text-white font-mono font-bold mt-3" style="font-size: clamp(1.5rem, 4vw, 3rem);" x-text="formattedDuration"></p>
                        @endif
                    @else
                        <p class="text-emerald-600 dark:text-emerald-400 text-base mt-2 uppercase tracking-wide font-semibold">operational</p>
                    @endif
                </div>

                <!-- Last Check Info -->
                <div class="relative z-10 w-full mt-auto pt-3">
                    @if($monitor->latest_check)
                        <div @class([
                            'flex items-center justify-center gap-4 font-mono',
                            'text-red-100/80' => $monitor->has_active_anomaly,
                            'text-neutral-500 dark:text-neutral-400' => !$monitor->has_active_anomaly,
                        ])
                        style="font-size: clamp(0.75rem, 1.2vw, 1rem);"
                        >
                            @if($monitor->latest_check->response_time)
                                <span class="flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    {{ round($monitor->latest_check->response_time) }}ms
                                </span>
                            @endif

                            @if($monitor->latest_check->response_code)
                                <span @class([
                                    'px-2 py-1 rounded font-medium',
                                    'bg-red-700/50 text-red-100' => $monitor->has_active_anomaly,
                                    'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' => !$monitor->has_active_anomaly && $monitor->latest_check->response_code >= 200 && $monitor->latest_check->response_code < 300,
                                    'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400' => !$monitor->has_active_anomaly && $monitor->latest_check->response_code >= 300 && $monitor->latest_check->response_code < 400,
                                    'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' => !$monitor->has_active_anomaly && $monitor->latest_check->response_code >= 400,
                                ])>
                                    {{ $monitor->latest_check->response_code }}
                                </span>
                            @endif

                            <span class="flex items-center gap-1.5" title="{{ $monitor->latest_check->checked_at->format('Y-m-d H:i:s') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ $monitor->latest_check->checked_at->diffForHumans(short: true) }}
                            </span>
                        </div>

                        @if(count($monitor->response_times) > 0)
                            @php
                                $avgTime = round(array_sum($monitor->response_times) / count($monitor->response_times));
                            @endphp
                            <p @class([
                                'text-center mt-1',
                                'text-red-100/60' => $monitor->has_active_anomaly,
                                'text-neutral-400 dark:text-neutral-500' => !$monitor->has_active_anomaly,
                            ])
                            style="font-size: clamp(0.7rem, 1vw, 0.875rem);"
                            >
                                avg {{ $avgTime }}ms
                            </p>
                        @endif
                    @elseif($monitor->last_checked_at)
                        <p @class([
                            'text-center',
                            'text-red-100/60' => $monitor->has_active_anomaly,
                            'text-neutral-400 dark:text-neutral-500' => !$monitor->has_active_anomaly,
                        ])
                        style="font-size: clamp(0.75rem, 1.2vw, 1rem);"
                        >
                            checked {{ $monitor->last_checked_at->diffForHumans(short: true) }}
                        </p>
                    @endif
                </div>
            </div>
        @endforeach

        @if($this->displayMonitors->isEmpty())
            <div class="col-span-full flex items-center justify-center">
                <div class="text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-neutral-300 dark:text-neutral-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <p class="text-neutral-500 dark:text-neutral-400 text-lg">no monitors selected</p>
                    <p class="text-neutral-400 dark:text-neutral-500 text-sm mt-1">click the settings icon to choose monitors</p>
                </div>
            </div>
        @endif
    </div>
</div>

@script
<script>
    Alpine.data('monitoringWall', (monitors, downCount) => ({
        monitors: monitors,
        downCount: downCount,
        selectedMonitors: [],
        settingsOpen: false,
        darkMode: localStorage.getItem('monitoringWallDarkMode') === 'true',

        get allSelected() {
            return this.selectedMonitors.length === Object.keys(this.monitors).length;
        },

        get gridStyle() {
            const count = this.selectedMonitors.length;
            if (count === 0) return 'grid-template-columns: 1fr';

            const healthyCount = count - this.downCount;
            const effectiveCells = (this.downCount * 4) + healthyCount;
            const minCols = this.downCount > 0 ? 2 : 1;
            const aspectRatio = window.innerWidth / window.innerHeight;

            // Try all reasonable column counts, pick the one with best fit
            let bestCols = minCols;
            let bestScore = Infinity;

            for (let c = minCols; c <= Math.min(effectiveCells, 8); c++) {
                const r = Math.ceil(effectiveCells / c);
                const waste = (c * r) - effectiveCells;
                const ratio = c / r;
                const ratioError = Math.abs(ratio - aspectRatio);
                // Penalize empty cells heavily, slightly prefer matching screen aspect ratio
                const score = (waste * 3) + ratioError;

                if (score < bestScore) {
                    bestScore = score;
                    bestCols = c;
                }
            }

            return `grid-template-columns: repeat(${bestCols}, 1fr)`;
        },

        init() {
            const saved = localStorage.getItem('monitoringWallSelection');
            if (saved) {
                try {
                    this.selectedMonitors = JSON.parse(saved);
                } catch (e) {
                    this.selectedMonitors = [];
                }
            }
            // Default: empty selection, user picks via settings

            this.syncWithLivewire();

            window.addEventListener('resize', () => {
                this.$forceUpdate;
            });
        },

        saveSelection() {
            localStorage.setItem('monitoringWallSelection', JSON.stringify(this.selectedMonitors));
            this.syncWithLivewire();
        },

        syncWithLivewire() {
            $wire.updateSelectedMonitors(this.selectedMonitors);
        },

        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('monitoringWallDarkMode', this.darkMode);
            document.documentElement.classList.toggle('dark', this.darkMode);
        },

        toggleAllMonitors() {
            if (this.allSelected) {
                this.selectedMonitors = [];
            } else {
                this.selectedMonitors = Object.values(this.monitors);
            }
            this.saveSelection();
        }
    }));

    Alpine.data('downtimeCounter', (startedAt) => ({
        startedAt: new Date(startedAt),
        formattedDuration: '',
        interval: null,

        init() {
            this.updateDuration();
            this.interval = setInterval(() => this.updateDuration(), 1000);
        },

        destroy() {
            if (this.interval) {
                clearInterval(this.interval);
            }
        },

        updateDuration() {
            const now = new Date();
            const diff = Math.floor((now - this.startedAt) / 1000);

            if (diff < 0) {
                this.formattedDuration = '0s';
                return;
            }

            const days = Math.floor(diff / 86400);
            const hours = Math.floor((diff % 86400) / 3600);
            const minutes = Math.floor((diff % 3600) / 60);
            const seconds = diff % 60;

            let parts = [];

            if (days > 0) {
                parts.push(`${days}d`);
            }
            if (hours > 0 || days > 0) {
                parts.push(`${hours}h`);
            }
            if (minutes > 0 || hours > 0 || days > 0) {
                parts.push(`${minutes}m`);
            }
            parts.push(`${seconds}s`);

            this.formattedDuration = parts.join(' ');
        }
    }));
</script>
@endscript
