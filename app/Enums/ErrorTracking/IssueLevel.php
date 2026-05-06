<?php

namespace App\Enums\ErrorTracking;

use App\Enums\Concerns\ResolvesFromValue;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum IssueLevel: string implements HasColor, HasLabel
{
    use ResolvesFromValue;

    case FATAL = 'fatal';
    case ERROR = 'error';
    case WARNING = 'warning';
    case INFO = 'info';
    case DEBUG = 'debug';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::FATAL => 'danger',
            self::ERROR => 'danger',
            self::WARNING => 'warning',
            self::INFO => 'info',
            self::DEBUG => 'gray',
        };
    }

    public function severityRank(): int
    {
        return match ($this) {
            self::DEBUG => 1,
            self::INFO => 2,
            self::WARNING => 3,
            self::ERROR => 4,
            self::FATAL => 5,
        };
    }

    public function isAtLeast(self $other): bool
    {
        return $this->severityRank() >= $other->severityRank();
    }
}
