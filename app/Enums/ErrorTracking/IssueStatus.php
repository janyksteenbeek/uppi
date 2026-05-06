<?php

namespace App\Enums\ErrorTracking;

use App\Enums\Concerns\ResolvesFromValue;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum IssueStatus: string implements HasColor, HasIcon, HasLabel
{
    use ResolvesFromValue;

    case OPEN = 'open';
    case RESOLVED = 'resolved';
    case IGNORED = 'ignored';

    public function getLabel(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::RESOLVED => 'Resolved',
            self::IGNORED => 'Ignored',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::OPEN => 'danger',
            self::RESOLVED => 'success',
            self::IGNORED => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::OPEN => 'phosphor-warning-circle',
            self::RESOLVED => 'phosphor-check-circle',
            self::IGNORED => 'phosphor-eye-slash',
        };
    }
}
