<?php

namespace App\Enums\Tests;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TestStatus: string implements HasIcon, HasColor, HasLabel
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case SUCCESS = 'success';
    case FAILURE = 'failure';

    public function getIcon(): string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::RUNNING => 'heroicon-o-arrow-path',
            self::SUCCESS => 'heroicon-o-check-circle',
            self::FAILURE => 'heroicon-o-x-circle',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::RUNNING => 'info',
            self::SUCCESS => 'success',
            self::FAILURE => 'danger',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::RUNNING => 'Running',
            self::SUCCESS => 'Success',
            self::FAILURE => 'Failed',
        };
    }

    public function isSuccess(): bool
    {
        return $this === self::SUCCESS;
    }

    public function isFailure(): bool
    {
        return $this === self::FAILURE;
    }

    public function isFinished(): bool
    {
        return $this === self::SUCCESS || $this === self::FAILURE;
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])->toArray();
    }
}
