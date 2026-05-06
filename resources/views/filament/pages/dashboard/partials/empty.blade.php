@php
    $tone = $tone ?? 'neutral';
    $iconColor = match ($tone) {
        'good' => 'var(--pulse-green)',
        'bad'  => 'var(--pulse-red)',
        default => 'var(--pulse-muted)',
    };
@endphp

<div class="pulse-empty">
    <div class="pulse-empty-icon" style="color: {{ $iconColor }};">
        <x-filament::icon :icon="$icon ?? 'phosphor-sparkle'" class="h-7 w-7" />
    </div>
    <p class="pulse-empty-title">{{ $title ?? '' }}</p>
    @if (!empty($lede))
        <p class="pulse-empty-lede">{{ $lede }}</p>
    @endif
</div>
