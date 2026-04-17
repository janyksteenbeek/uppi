<?php

namespace App\Enums\Checks;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasColor;

enum Status: string implements HasIcon, HasColor
{
    case OK = 'ok';
    case FAIL = 'fail';
    case UNKNOWN = 'unknown';

    public function getIcon(): string
    {
        return match ($this) {
            self::OK => 'heroicon-o-check-circle',
            self::FAIL => 'heroicon-o-x-circle',
            self::UNKNOWN => 'heroicon-o-question-mark-circle',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::OK => 'success',
            self::FAIL => 'danger',
            self::UNKNOWN => 'warning',
        };
    }

    public function isUp(): bool
    {
        return $this === self::OK;
    }

    public function isDown(): bool
    {
        return $this === self::FAIL;
    }

    public function label(): string
    {
        return match ($this) {
            self::OK => 'OK',
            self::FAIL => 'Down',
            self::UNKNOWN => 'Unknown',
        };
    }
}
