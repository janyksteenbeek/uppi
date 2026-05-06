@php
    $rows = $rows ?? 5;
@endphp

<div class="space-y-3">
    @for ($i = 0; $i < $rows; $i++)
        <div class="flex items-center gap-3">
            <div class="pulse-skeleton h-4 w-16 rounded"></div>
            <div class="pulse-skeleton h-4 flex-1 rounded"></div>
            <div class="pulse-skeleton h-4 w-24 rounded"></div>
            <div class="pulse-skeleton h-4 w-24 rounded"></div>
        </div>
    @endfor
</div>
