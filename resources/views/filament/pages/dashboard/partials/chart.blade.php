@php
    $maxHeight = $maxHeight ?? '200px';
    $options = array_replace_recursive(\App\Support\ChartTheme::defaultOptions(), $options ?? []);
@endphp

<div
    x-load
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
    wire:ignore
    wire:key="chart-{{ $id }}"
    data-chart-type="{{ $type }}"
    x-data="chart({
        cachedData: @js($data),
        maxHeight: @js($maxHeight),
        options: @js($options),
        type: @js($type),
    })"
    class="fi-wi-chart-canvas-ctn fi-wi-chart-canvas-ctn-no-aspect-ratio"
>
    <canvas x-ref="canvas" style="max-height: {{ $maxHeight }}"></canvas>
    <span x-ref="backgroundColorElement" class="fi-wi-chart-bg-color"></span>
    <span x-ref="borderColorElement" class="fi-wi-chart-border-color"></span>
    <span x-ref="gridColorElement" class="fi-wi-chart-grid-color"></span>
    <span x-ref="textColorElement" class="fi-wi-chart-text-color"></span>
</div>
