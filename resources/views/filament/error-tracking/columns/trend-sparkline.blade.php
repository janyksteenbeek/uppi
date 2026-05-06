@php
    /** @var \App\Models\ErrorTracking\Issue $record */
    $buckets = $record->trend_buckets ?? null;
    if (! is_array($buckets)) {
        $since = now()->subDay()->startOfHour();
        $rows = \App\Models\ErrorTracking\Event::query()
            ->withoutGlobalScope('user')
            ->where('issue_id', $record->id)
            ->where('occurred_at', '>=', $since)
            ->pluck('occurred_at');

        $buckets = array_fill(0, 24, 0);
        foreach ($rows as $occurredAt) {
            $hours = (int) floor($since->diffInRealMinutes($occurredAt) / 60);
            if ($hours >= 0 && $hours < 24) {
                $buckets[$hours]++;
            }
        }
    }

    $max = max($buckets) ?: 1;
    $width = 96;
    $height = 28;
    $barWidth = $width / count($buckets);
@endphp

<div class="pulse-spark" style="height: {{ $height }}px; width: {{ $width }}px">
    @foreach ($buckets as $count)
        @php
            $h = $count > 0 ? max(2, (int) round(($count / $max) * $height)) : 2;
            $cls = $count > 0 ? 'pulse-spark-bar pulse-spark-bar-hot' : 'pulse-spark-bar';
        @endphp
        <div class="{{ $cls }}" style="height: {{ $h }}px; width: {{ $barWidth }}px"></div>
    @endforeach
</div>
