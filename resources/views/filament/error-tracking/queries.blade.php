@php
    /** @var array<int, array<string, mixed>> $queries */
    $queries = array_reverse($queries ?? []);

    $formatTime = function ($ts) {
        if (is_numeric($ts)) {
            try { return \Illuminate\Support\Carbon::createFromTimestamp((float) $ts)->format('H:i:s'); } catch (\Throwable) {}
        }
        if (is_string($ts) && $ts !== '') {
            try { return \Illuminate\Support\Carbon::parse($ts)->format('H:i:s'); } catch (\Throwable) {}
        }
        return '—';
    };

    $formatDuration = function ($crumb) {
        $data = $crumb['data'] ?? [];
        foreach (['db.duration', 'duration_ms', 'duration', 'time'] as $key) {
            if (isset($data[$key]) && is_numeric($data[$key])) {
                $value = (float) $data[$key];
                if ($key === 'duration' && $value < 1) {
                    $value *= 1000;
                }
                if ($value >= 1000) {
                    return number_format($value / 1000, 2).' s';
                }
                if ($value >= 1) {
                    return number_format($value, 1).' ms';
                }
                return number_format($value, 2).' ms';
            }
        }
        return null;
    };
@endphp

@if (count($queries) === 0)
    <p class="pulse-empty-lede" style="text-align:left;">No database queries captured.</p>
@else
    <ul style="display: flex; flex-direction: column; gap: 8px; margin: 0; padding: 0; list-style: none;">
        @foreach ($queries as $crumb)
            @php
                $data = $crumb['data'] ?? [];
                $sql = $data['db.statement'] ?? $data['sql'] ?? $data['query'] ?? $crumb['message'] ?? '';
                $bindings = $data['db.bindings'] ?? $data['bindings'] ?? null;
                $connection = $data['db.system'] ?? $data['db.connection'] ?? $data['connectionName'] ?? null;
                $duration = $formatDuration($crumb);
            @endphp
            <li style="border: 1px solid var(--pulse-line); border-radius: 6px; overflow: hidden;">
                <div style="display: flex; align-items: center; gap: 8px; padding: 6px 10px; background: var(--pulse-soft); font-size: 11px; color: var(--pulse-muted);">
                    <span style="font-family: ui-monospace, monospace;">{{ $formatTime($crumb['timestamp'] ?? null) }}</span>
                    @if ($connection)
                        <span class="pulse-issue-chip pulse-issue-chip-mute" style="font-size: 10px;">{{ $connection }}</span>
                    @endif
                    @if ($duration)
                        <span style="margin-left: auto; font-family: ui-monospace, monospace; color: var(--pulse-ink); font-weight: 500;">{{ $duration }}</span>
                    @endif
                </div>
                <pre style="margin: 0; padding: 8px 10px; background: #0b1020; color: #e6e9ef; font-size: 11px; line-height: 1.5; overflow-x: auto; white-space: pre-wrap; word-break: break-word;"><code>{{ $sql }}</code></pre>
                @if (is_array($bindings) && count($bindings) > 0)
                    <div style="padding: 6px 10px; background: var(--pulse-soft); font-size: 11px; color: var(--pulse-muted); border-top: 1px solid var(--pulse-line);">
                        <span style="color: var(--pulse-muted); font-weight: 500; margin-right: 6px;">bindings</span>
                        <span style="font-family: ui-monospace, monospace; color: var(--pulse-ink);">{{ json_encode(array_values($bindings), JSON_UNESCAPED_SLASHES) }}</span>
                    </div>
                @endif
            </li>
        @endforeach
    </ul>
@endif
