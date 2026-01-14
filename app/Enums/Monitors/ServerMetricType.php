<?php

namespace App\Enums\Monitors;

use Filament\Support\Contracts\HasLabel;

enum ServerMetricType: string implements HasLabel
{
    case CpuUsage = 'cpu_usage';
    case MemoryUsage = 'memory_usage';
    case SwapUsage = 'swap_usage';
    case LoadAverage = 'load_average';
    case DiskUsage = 'disk_usage';

    public function getLabel(): string
    {
        return match ($this) {
            self::CpuUsage => 'CPU Usage',
            self::MemoryUsage => 'Memory Usage',
            self::SwapUsage => 'Swap Usage',
            self::LoadAverage => 'Load Average',
            self::DiskUsage => 'Disk Usage',
        };
    }

    public function getUnit(): string
    {
        return match ($this) {
            self::CpuUsage, self::MemoryUsage, self::SwapUsage, self::DiskUsage => '%',
            self::LoadAverage => '',
        };
    }

    public function requiresDiskSelection(): bool
    {
        return $this === self::DiskUsage;
    }

    public function getDefaultThreshold(): float
    {
        return match ($this) {
            self::CpuUsage => 90,
            self::MemoryUsage => 90,
            self::SwapUsage => 80,
            self::LoadAverage => 10,
            self::DiskUsage => 90,
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
