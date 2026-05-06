@php
    /** @var \App\Models\ErrorTracking\Issue $record */
    $statusDot = match ($record->status?->value) {
        'open'     => 'var(--pulse-red)',
        'resolved' => 'var(--pulse-green)',
        'ignored'  => 'var(--pulse-muted)',
        default    => 'var(--pulse-line)',
    };

    $levelLabel = strtoupper($record->level?->value ?? 'error');
    $levelClass = match ($record->level?->value) {
        'fatal', 'error' => 'pulse-issue-tag pulse-issue-tag-bad',
        'warning'        => 'pulse-issue-tag pulse-issue-tag-warn',
        'info'           => 'pulse-issue-tag pulse-issue-tag-info',
        default          => 'pulse-issue-tag pulse-issue-tag-mute',
    };

    $envBadges = collect($record->latestEvent?->environment ? [$record->latestEvent->environment] : [])
        ->merge($record->latestEvent?->release ? ['rel '.$record->latestEvent->release] : [])
        ->take(2);
@endphp

<div class="pulse-issue-cell">
    <span class="pulse-issue-status" style="background: {{ $statusDot }};"></span>

    <div class="pulse-issue-body">
        <div class="pulse-issue-head">
            <span class="{{ $levelClass }}">{{ $levelLabel }}</span>
            <span class="pulse-issue-title" title="{{ $record->title }}">
                {{ $record->title }}
            </span>
        </div>

        @if ($record->culprit)
            <p class="pulse-issue-culprit" title="{{ $record->culprit }}">
                {{ $record->culprit }}
            </p>
        @endif

        @if ($envBadges->isNotEmpty() || $record->project)
            <div class="pulse-issue-meta">
                @if ($record->project)
                    <span class="pulse-issue-chip">
                        <i class="dot"></i>
                        {{ $record->project->name }}
                    </span>
                @endif
                @foreach ($envBadges as $badge)
                    <span class="pulse-issue-chip pulse-issue-chip-mute">{{ $badge }}</span>
                @endforeach
            </div>
        @endif
    </div>
</div>
