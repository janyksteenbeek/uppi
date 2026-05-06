<?php

namespace App\Enums\ErrorTracking;

use App\Enums\Concerns\ResolvesFromValue;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum IssueAlertCondition: string implements HasIcon, HasLabel
{
    use ResolvesFromValue;

    case FIRST_SEEN = 'first_seen';
    case EVENT_COUNT_THRESHOLD = 'event_count_threshold';
    case REGRESSION = 'regression';

    public function getLabel(): string
    {
        return match ($this) {
            self::FIRST_SEEN => 'First seen',
            self::EVENT_COUNT_THRESHOLD => 'Event count threshold',
            self::REGRESSION => 'Regression',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::FIRST_SEEN => 'Triggers the first time an issue is observed.',
            self::EVENT_COUNT_THRESHOLD => 'Triggers when an issue receives N events within a rolling window.',
            self::REGRESSION => 'Triggers when a previously resolved issue receives a new event.',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::FIRST_SEEN => 'phosphor-sparkle',
            self::EVENT_COUNT_THRESHOLD => 'phosphor-chart-bar',
            self::REGRESSION => 'phosphor-arrow-u-up-left',
        };
    }
}
