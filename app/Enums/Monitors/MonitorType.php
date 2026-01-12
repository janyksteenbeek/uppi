<?php

namespace App\Enums\Monitors;

use App\Jobs\Checks\DummyCheckJob;
use App\Jobs\Checks\HttpCheckJob;
use App\Jobs\Checks\PulseCheckJob;
use App\Jobs\Checks\TcpCheckJob;
use App\Jobs\Checks\TestCheckJob;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum MonitorType: string implements HasIcon, HasLabel
{
    case HTTP = 'http';
    case TCP = 'tcp';
    case DUMMY = 'dummy';
    case PULSE = 'pulse';
    case TEST = 'test';

    public function toCheckJob(): string
    {
        return match ($this) {
            self::HTTP => HttpCheckJob::class,
            self::TCP => TcpCheckJob::class,
            self::DUMMY => DummyCheckJob::class,
            self::PULSE => PulseCheckJob::class,
            self::TEST => TestCheckJob::class,
        };
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::HTTP => 'HTTP',
            self::TCP => 'TCP',
            self::DUMMY => '',
            self::PULSE => 'Check-in',
            self::TEST => 'Test',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::HTTP => 'heroicon-o-globe-alt',
            self::TCP => 'heroicon-o-server-stack',
            self::DUMMY => 'heroicon-o-question-mark-circle',
            self::PULSE => 'heroicon-o-clock',
            self::TEST => 'heroicon-o-beaker',
        };
    }

    public static function options(): array
    {
        $options = [
            self::HTTP->value => self::HTTP->getLabel(),
            self::TCP->value => self::TCP->getLabel(),
            self::PULSE->value => self::PULSE->getLabel(),
        ];

        if (auth()->check() && auth()->user()->hasFeature('run-tests')) {
            $options[self::TEST->value] = self::TEST->getLabel();
        }

        return $options;
    }

    public static function allOptions(): array
    {
        return [
            self::HTTP->value => self::HTTP->getLabel(),
            self::TCP->value => self::TCP->getLabel(),
            self::PULSE->value => self::PULSE->getLabel(),
            self::TEST->value => self::TEST->getLabel(),
        ];
    }
}
