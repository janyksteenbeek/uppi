@php
    $color = $kpi['color'] ?? 'primary';

    $tones = [
        'success' => ['dot' => 'var(--pulse-green)',  'value' => 'var(--pulse-ink)'],
        'danger'  => ['dot' => 'var(--pulse-red)',    'value' => 'var(--pulse-red)'],
        'warning' => ['dot' => '#D6A437',             'value' => 'var(--pulse-ink)'],
        'primary' => ['dot' => 'var(--pulse-red)',    'value' => 'var(--pulse-ink)'],
        'gray'    => ['dot' => 'var(--pulse-muted)',  'value' => 'var(--pulse-muted)'],
    ];

    $tone = $tones[$color] ?? $tones['primary'];
@endphp

<div class="pulse-kpi">
    <div class="pulse-kpi-head">
        <span class="pulse-kpi-label">{{ $kpi['label'] }}</span>
        <span class="pulse-kpi-dot" style="background: {{ $tone['dot'] }};"></span>
    </div>
    <div class="pulse-kpi-value" style="color: {{ $tone['value'] }};">
        {{ $kpi['value'] }}
    </div>
    @if (!empty($kpi['hint']))
        <div class="pulse-kpi-hint">{{ $kpi['hint'] }}</div>
    @endif
</div>

@once
    <style>
        .pulse-kpi {
            background: var(--pulse-paper);
            border: 1px solid var(--pulse-line);
            border-radius: 14px;
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-height: 108px;
            transition: border-color .2s ease, transform .15s ease;
        }
        .pulse-kpi:hover {
            border-color: var(--pulse-ink);
        }
        .pulse-kpi-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        .pulse-kpi-label {
            font-family: var(--pulse-mono);
            font-size: 10px;
            color: var(--pulse-muted);
            text-transform: uppercase;
            letter-spacing: 0.12em;
            line-height: 1.2;
        }
        .pulse-kpi-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .pulse-kpi-value {
            font-family: var(--pulse-display);
            font-weight: 400;
            font-size: 32px;
            line-height: 1;
            letter-spacing: -0.03em;
            margin-top: 4px;
            font-variant-numeric: tabular-nums;
        }
        .pulse-kpi-hint {
            font-family: var(--pulse-body);
            font-size: 12px;
            color: var(--pulse-muted);
            line-height: 1.4;
            margin-top: auto;
        }
    </style>
@endonce
