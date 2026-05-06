<?php

namespace App\Enums\StatusPage;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum UpdateStatus: string implements HasColor, HasIcon, HasLabel
{
    case NEW = 'new';
    case UNDER_INVESTIGATION = 'under_investigation';
    case IDENTIFIED = 'identified';
    case WORK_IN_PROGRESS = 'work_in_progress';
    case RECOVERING = 'recovering';
    case MONITORING = 'monitoring';
    case POST_INCIDENT = 'post_incident';
    case COMPLETED = 'completed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NEW => 'New',
            self::UNDER_INVESTIGATION => 'Under Investigation',
            self::IDENTIFIED => 'Identified',
            self::WORK_IN_PROGRESS => 'Work in Progress',
            self::RECOVERING => 'Recovering',
            self::MONITORING => 'Monitoring',
            self::POST_INCIDENT => 'Post Incident',
            self::COMPLETED => 'Completed',
        };
    }

    public function getColor(): array|string|null
    {
        return match ($this) {
            self::NEW => 'danger',
            self::UNDER_INVESTIGATION => 'danger',
            self::IDENTIFIED => 'warning',
            self::WORK_IN_PROGRESS => 'warning',
            self::RECOVERING => 'warning',
            self::MONITORING => 'warning',
            self::POST_INCIDENT => 'success',
            self::COMPLETED => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::NEW => 'phosphor-warning',
            self::UNDER_INVESTIGATION => 'phosphor-magnifying-glass',
            self::IDENTIFIED => 'phosphor-sparkle',
            self::WORK_IN_PROGRESS => 'phosphor-wrench',
            self::RECOVERING => 'phosphor-wrench',
            self::MONITORING => 'phosphor-check',
            self::POST_INCIDENT => 'phosphor-arrow-circle-up',
            self::COMPLETED => 'phosphor-check-circle',
        };
    }
}
