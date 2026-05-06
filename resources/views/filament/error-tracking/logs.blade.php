@php
    /** @var array<int, array<string, mixed>> $logs */
    $logs = array_reverse($logs ?? []);

    $formatTime = function ($ts) {
        if (is_numeric($ts)) {
            try { return \Illuminate\Support\Carbon::createFromTimestamp((float) $ts)->format('H:i:s'); } catch (\Throwable) {}
        }
        if (is_string($ts) && $ts !== '') {
            try { return \Illuminate\Support\Carbon::parse($ts)->format('H:i:s'); } catch (\Throwable) {}
        }
        return '—';
    };

    $levelColor = function (string $level): string {
        return match (strtolower($level)) {
            'fatal', 'error', 'critical', 'emergency' => 'var(--pulse-red)',
            'warning', 'warn' => '#f59e0b',
            'info', 'notice' => '#3b82f6',
            'debug' => 'var(--pulse-muted)',
            default => 'var(--pulse-muted)',
        };
    };
@endphp

@if (count($logs) === 0)
    <p class="pulse-empty-lede" style="text-align:left;">No log entries captured.</p>
@else
    <ul style="display: flex; flex-direction: column; gap: 6px; margin: 0; padding: 0; list-style: none;">
        @foreach ($logs as $crumb)
            @php
                $level = $crumb['level'] ?? str_replace('log.', '', strtolower((string) ($crumb['category'] ?? 'info')));
                $message = $crumb['message'] ?? '';
                $data = $crumb['data'] ?? [];
            @endphp
            <li style="display: flex; gap: 10px; padding: 6px 10px; border: 1px solid var(--pulse-line); border-radius: 6px; align-items: flex-start;">
                <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 11px; color: var(--pulse-muted); font-family: ui-monospace, monospace; flex-shrink: 0;">
                    <span style="display: inline-block; height: 6px; width: 6px; border-radius: 50%; background: {{ $levelColor((string) $level) }};"></span>
                    {{ $formatTime($crumb['timestamp'] ?? null) }}
                </span>
                <span style="font-size: 10px; font-weight: 600; color: {{ $levelColor((string) $level) }}; text-transform: uppercase; letter-spacing: 0.04em; flex-shrink: 0; padding-top: 1px;">
                    {{ $level }}
                </span>
                <div style="min-width: 0; flex: 1;">
                    <p style="margin: 0; font-size: 12px; color: var(--pulse-ink); word-break: break-word; line-height: 1.45;">{{ $message }}</p>
                    @if (is_array($data) && count($data) > 0)
                        <details style="margin-top: 4px;">
                            <summary style="cursor: pointer; font-size: 10px; color: var(--pulse-muted);">context</summary>
                            <pre style="margin: 4px 0 0; padding: 6px 8px; background: var(--pulse-soft); border-radius: 4px; font-size: 10px; line-height: 1.4; overflow-x: auto;"><code>{{ json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                        </details>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
@endif
