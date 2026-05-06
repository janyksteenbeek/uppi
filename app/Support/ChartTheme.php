<?php

namespace App\Support;

class ChartTheme
{
    // Pulse palette — restrained, paper-friendly, signature red leads.
    public const PALETTE = [
        '#E5392E', // pulse red
        '#1F8A5B', // pulse green
        '#2A6FDB', // calm blue
        '#3A3A40', // ink-2
        '#A1781F', // ochre
        '#0E8A97', // teal
        '#7A4FBF', // muted violet
        '#C2271E', // deep red
        '#5E5E66', // ink mid
        '#9E8B5E', // tan
    ];

    public static function colorForKey(string $key): string
    {
        $index = abs((int) crc32($key)) % count(self::PALETTE);

        return self::PALETTE[$index];
    }

    public static function colorForKeyRgba(string $key, float $alpha = 1.0): string
    {
        return self::hexToRgba(self::colorForKey($key), $alpha);
    }

    public static function hexToRgba(string $hex, float $alpha = 1.0): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        [$r, $g, $b] = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];

        return "rgba($r, $g, $b, $alpha)";
    }

    public static function defaultOptions(): array
    {
        return [
            'animation' => ['duration' => 600, 'easing' => 'easeOutQuart'],
            'plugins' => [
                'tooltip' => [
                    'enabled' => true,
                    'backgroundColor' => 'rgba(14, 14, 16, 0.96)',
                    'titleColor' => '#FBFAF7',
                    'bodyColor' => 'rgba(251, 250, 247, 0.85)',
                    'titleFont' => ['weight' => '500', 'size' => 12, 'family' => "'Geist Mono', ui-monospace, monospace"],
                    'bodyFont' => ['size' => 12, 'weight' => '400', 'family' => "'Geist Mono', ui-monospace, monospace"],
                    'titleMarginBottom' => 6,
                    'padding' => 12,
                    'cornerRadius' => 10,
                    'displayColors' => true,
                    'boxPadding' => 6,
                    'borderColor' => 'rgba(255, 255, 255, 0.04)',
                    'borderWidth' => 1,
                    'caretSize' => 0,
                    'usePointStyle' => true,
                ],
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'padding' => 16,
                        'boxWidth' => 8,
                        'boxHeight' => 8,
                        'font' => ['size' => 12, 'weight' => '500'],
                    ],
                ],
            ],
            'pointRadius' => 0,
            'pointHoverRadius' => 5,
            'pointHitRadius' => 12,
            'borderWidth' => 2.5,
            'scales' => [
                'x' => [
                    'ticks' => ['padding' => 8, 'font' => ['size' => 11]],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['padding' => 8, 'font' => ['size' => 11], 'maxTicksLimit' => 5],
                    'grid' => ['drawTicks' => false],
                ],
            ],
            'interaction' => ['intersect' => false, 'mode' => 'index'],
            'layout' => ['padding' => ['top' => 8, 'right' => 8, 'bottom' => 0, 'left' => 0]],
            'elements' => [
                'line' => ['tension' => 0.4, 'borderCapStyle' => 'round', 'borderJoinStyle' => 'round'],
                'bar' => ['borderRadius' => 6, 'borderSkipped' => false, 'maxBarThickness' => 32],
            ],
        ];
    }
}
