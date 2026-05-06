@php
    $state = $hero['state'] ?? 'ok';
    $health = $hero['health_percentage'] ?? null;
    $up = $hero['up'] ?? 0;
    $down = $hero['down'] ?? 0;
    $unknown = $hero['unknown'] ?? 0;
    $disabled = $hero['disabled'] ?? 0;
    $total = $hero['total'] ?? 0;
    $activeIncidents = $hero['active_incidents'] ?? 0;

    $eyebrow = match ($state) {
        'empty'     => 'no monitors yet',
        'down'      => 'systems offline',
        'incidents' => 'active incidents',
        'pending'   => 'awaiting first checks',
        default     => 'all systems operational',
    };

    $headline = match ($state) {
        'empty'     => 'Add your first monitor',
        'down'      => $down . ' ' . ($down === 1 ? 'monitor is' : 'monitors are') . ' down',
        'incidents' => $activeIncidents . ' active ' . ($activeIncidents === 1 ? 'incident' : 'incidents'),
        'pending'   => 'Standing by',
        default     => 'Everything’s steady',
    };

    $isHealthy = in_array($state, ['ok', 'pending', 'empty'], true);
    $bigColor = $isHealthy ? 'var(--pulse-ink)' : 'var(--pulse-red)';
    $dotColor = match ($state) {
        'ok'        => 'var(--pulse-green)',
        'down'      => 'var(--pulse-red)',
        'incidents' => 'var(--pulse-red)',
        'pending'   => '#D6A437',
        default     => 'var(--pulse-muted)',
    };
@endphp

<div class="pulse-hero-card">
    <div class="pulse-hero-grid">
        {{-- Left: eyebrow + headline + breakdown --}}
        <div class="pulse-hero-main">
            <div class="pulse-hero-eyebrow">
                <span class="pulse-dot-static" style="background: {{ $dotColor }};"></span>
                {{ $eyebrow }}
            </div>

            <h2 class="pulse-hero-title">
                @if ($state === 'incidents')
                    {{ $activeIncidents }} active <em>{{ $activeIncidents === 1 ? 'incident' : 'incidents' }}</em>
                @elseif ($state === 'down')
                    {{ $down }} {{ $down === 1 ? 'monitor' : 'monitors' }} <em>down</em>
                @elseif ($state === 'empty')
                    Add your <em>first</em> monitor
                @elseif ($state === 'pending')
                    <em>Standing</em> by
                @else
                    Everything’s <em>steady</em>
                @endif
            </h2>

            <div class="pulse-hero-pills">
                <span class="pulse-pill {{ $up > 0 ? 'pulse-pill-ok' : 'pulse-pill-mute' }}">
                    <i class="dot" style="background: var(--pulse-green);"></i>
                    {{ $up }} up
                </span>
                <span class="pulse-pill {{ $down > 0 ? 'pulse-pill-bad' : 'pulse-pill-mute' }}">
                    <i class="dot" style="background: {{ $down > 0 ? 'var(--pulse-red)' : 'var(--pulse-muted)' }};"></i>
                    {{ $down }} down
                </span>
                <span class="pulse-pill pulse-pill-mute">
                    <i class="dot" style="background: var(--pulse-muted);"></i>
                    {{ $unknown }} unknown
                </span>
                @if ($disabled > 0)
                    <span class="pulse-pill pulse-pill-mute">
                        <i class="dot" style="background: var(--pulse-line);"></i>
                        {{ $disabled }} paused
                    </span>
                @endif
            </div>

            <p class="pulse-hero-lede">
                @if ($total === 0)
                    Add a monitor to start tracking your systems.
                @else
                    Tracking <b>{{ $total }}</b> enabled {{ $total === 1 ? 'monitor' : 'monitors' }}.
                    @if ($activeIncidents > 0)
                        <span class="text-red">{{ $activeIncidents }} active {{ $activeIncidents === 1 ? 'incident' : 'incidents' }}.</span>
                    @else
                        No active incidents.
                    @endif
                @endif
            </p>
        </div>

        {{-- Right: donut readout --}}
        <div class="pulse-hero-readout">
            <div class="pulse-hero-readout-lbl">fig. 01 · health</div>
            <div class="pulse-hero-readout-stack">
                <div class="pulse-hero-donut">
                    @if ($total > 0)
                        @include('filament.pages.dashboard.partials.chart', [
                            'id' => 'status-donut',
                            'type' => 'doughnut',
                            'data' => $statusDonut,
                            'maxHeight' => '110px',
                            'options' => [
                                'maintainAspectRatio' => false,
                                'cutout' => '78%',
                                'plugins' => [
                                    'legend' => ['display' => false],
                                    'tooltip' => ['enabled' => true],
                                ],
                            ],
                        ])
                    @else
                        <div class="pulse-hero-donut-empty"></div>
                    @endif
                    <div class="pulse-hero-donut-center">
                        @if ($health !== null)
                            <span class="pulse-hero-pct" style="color: {{ $bigColor }};">{{ number_format($health, 1) }}<small>%</small></span>
                        @else
                            <span class="pulse-hero-pct pulse-hero-pct-mute">—</span>
                        @endif
                    </div>
                </div>
                <div class="pulse-hero-readout-cap">healthy</div>
            </div>
        </div>
    </div>
</div>

@once
    <style>
        .pulse-hero-card {
            background: var(--pulse-paper);
            border: 1px solid var(--pulse-line);
            border-radius: 16px;
            overflow: hidden;
        }
        .pulse-hero-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0;
        }
        @media (min-width: 768px) {
            .pulse-hero-grid {
                grid-template-columns: 1fr 220px;
            }
        }
        .pulse-hero-main {
            padding: 24px 28px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .pulse-hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-family: var(--pulse-mono);
            font-size: 11px;
            color: var(--pulse-muted);
            text-transform: uppercase;
            letter-spacing: 0.14em;
        }
        .pulse-dot-static {
            width: 7px; height: 7px; border-radius: 50%; display: inline-block;
        }
        .pulse-hero-title {
            font-family: var(--pulse-display);
            font-weight: 400;
            font-size: 30px;
            line-height: 1.08;
            letter-spacing: -0.025em;
            color: var(--pulse-ink);
            margin: 0;
        }
        .pulse-hero-title em {
            font-style: italic;
            color: var(--pulse-red);
            font-weight: 300;
        }
        .pulse-hero-pills {
            display: flex; flex-wrap: wrap; gap: 8px;
        }
        .pulse-pill {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 5px 11px;
            border-radius: 999px;
            border: 1px solid var(--pulse-line);
            background: var(--pulse-bg);
            font-family: var(--pulse-mono);
            font-size: 11px;
            color: var(--pulse-ink-2);
            letter-spacing: 0.02em;
        }
        .pulse-pill .dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
        .pulse-pill-ok  { color: var(--pulse-ink); }
        .pulse-pill-bad { color: var(--pulse-red); border-color: rgba(229, 57, 46, 0.25); background: rgba(229, 57, 46, 0.05); }
        .pulse-pill-mute { color: var(--pulse-muted); }
        .pulse-hero-lede {
            font-size: 13px;
            line-height: 1.55;
            color: var(--pulse-ink-2);
            margin: 0;
            max-width: 60ch;
        }
        .pulse-hero-lede b { color: var(--pulse-ink); font-weight: 600; }
        .pulse-hero-lede .text-red { color: var(--pulse-red); font-weight: 500; }

        .pulse-hero-readout {
            border-top: 1px solid var(--pulse-line);
            padding: 20px 24px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            justify-content: center;
            position: relative;
        }
        @media (min-width: 768px) {
            .pulse-hero-readout {
                border-top: 0;
                border-left: 1px solid var(--pulse-line);
            }
        }
        .pulse-hero-readout-lbl {
            font-family: var(--pulse-mono);
            font-size: 10px;
            color: var(--pulse-muted);
            text-transform: uppercase;
            letter-spacing: 0.14em;
        }
        .pulse-hero-readout-stack {
            display: flex; flex-direction: column; align-items: flex-start; gap: 8px;
        }
        .pulse-hero-donut {
            position: relative;
            width: 116px; height: 116px;
        }
        .pulse-hero-donut-empty {
            width: 100%; height: 100%; border-radius: 50%;
            border: 1px dashed var(--pulse-line);
        }
        .pulse-hero-donut-center {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            pointer-events: none;
        }
        .pulse-hero-pct {
            font-family: var(--pulse-display);
            font-size: 26px; font-weight: 400;
            letter-spacing: -0.03em;
            line-height: 1;
        }
        .pulse-hero-pct small {
            font-family: var(--pulse-mono);
            font-size: 13px; color: var(--pulse-muted);
            letter-spacing: 0;
            margin-left: 1px;
        }
        .pulse-hero-pct-mute { color: var(--pulse-muted); }

        .pulse-hero-readout-cap {
            font-family: var(--pulse-mono);
            font-size: 11px;
            color: var(--pulse-muted);
            letter-spacing: 0.04em;
        }
    </style>
@endonce
