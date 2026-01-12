<?php

namespace App\Enums\Tests;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TestFlowBlockType: string implements HasIcon, HasColor, HasLabel
{
    case VISIT = 'visit';
    case WAIT_FOR_TEXT = 'wait_for_text';
    case TYPE = 'type';
    case SELECT = 'select';
    case CHECK = 'check';
    case UNCHECK = 'uncheck';
    case PRESS = 'press';
    case CLICK_LINK = 'click_link';
    case CLICK = 'click';
    case BACK = 'back';
    case FORWARD = 'forward';
    case REFRESH = 'refresh';
    case SCREENSHOT = 'screenshot';
    case SUCCESS = 'success';

    public function getIcon(): string
    {
        return match ($this) {
            self::VISIT => 'heroicon-o-globe-alt',
            self::WAIT_FOR_TEXT => 'heroicon-o-clock',
            self::TYPE => 'heroicon-o-pencil',
            self::SELECT => 'heroicon-o-chevron-up-down',
            self::CHECK => 'heroicon-o-check-circle',
            self::UNCHECK => 'heroicon-o-x-circle',
            self::PRESS => 'heroicon-o-cursor-arrow-rays',
            self::CLICK_LINK => 'heroicon-o-link',
            self::CLICK => 'heroicon-o-cursor-arrow-ripple',
            self::BACK => 'heroicon-o-arrow-left',
            self::FORWARD => 'heroicon-o-arrow-right',
            self::REFRESH => 'heroicon-o-arrow-path',
            self::SCREENSHOT => 'heroicon-o-camera',
            self::SUCCESS => 'heroicon-o-flag',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::VISIT => 'info',
            self::WAIT_FOR_TEXT => 'warning',
            self::TYPE => 'primary',
            self::SELECT => 'primary',
            self::CHECK => 'success',
            self::UNCHECK => 'danger',
            self::PRESS => 'success',
            self::CLICK_LINK => 'info',
            self::CLICK => 'warning',
            self::BACK => 'gray',
            self::FORWARD => 'gray',
            self::REFRESH => 'gray',
            self::SCREENSHOT => 'purple',
            self::SUCCESS => 'success',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::VISIT => 'Visit URL',
            self::WAIT_FOR_TEXT => 'Wait for text',
            self::TYPE => 'Type text',
            self::SELECT => 'Select option',
            self::CHECK => 'Check checkbox',
            self::UNCHECK => 'Uncheck checkbox',
            self::PRESS => 'Press button',
            self::CLICK_LINK => 'Click link',
            self::CLICK => 'Click element',
            self::BACK => 'Go back',
            self::FORWARD => 'Go forward',
            self::REFRESH => 'Refresh page',
            self::SCREENSHOT => 'Take screenshot',
            self::SUCCESS => 'Success',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::VISIT => 'Navigate to a specific URL',
            self::WAIT_FOR_TEXT => 'Wait until specific text appears on the page',
            self::TYPE => 'Type text into a form field (use field name or CSS selector)',
            self::SELECT => 'Select a value from a dropdown (use field name or CSS selector)',
            self::CHECK => 'Check a checkbox (use field name or CSS selector)',
            self::UNCHECK => 'Uncheck a checkbox (use field name or CSS selector)',
            self::PRESS => 'Click a button element by its text',
            self::CLICK_LINK => 'Click a link by its text',
            self::CLICK => 'Click an element by CSS selector',
            self::BACK => 'Navigate back in browser history',
            self::FORWARD => 'Navigate forward in browser history',
            self::REFRESH => 'Refresh the current page',
            self::SCREENSHOT => 'Take a screenshot and save it',
            self::SUCCESS => 'Mark the test as successful at this point',
        };
    }

    public function requiresValue(): bool
    {
        return match ($this) {
            self::VISIT, self::WAIT_FOR_TEXT, self::TYPE, self::PRESS, self::CLICK_LINK, self::SELECT => true,
            self::CHECK, self::UNCHECK, self::CLICK, self::BACK, self::FORWARD, self::REFRESH, self::SCREENSHOT, self::SUCCESS => false,
        };
    }

    public function requiresSelector(): bool
    {
        return match ($this) {
            self::TYPE, self::SELECT, self::CHECK, self::UNCHECK, self::CLICK => true,
            default => false,
        };
    }

    public function getValueLabel(): ?string
    {
        return match ($this) {
            self::VISIT => 'URL',
            self::WAIT_FOR_TEXT => 'Text to wait for',
            self::TYPE => 'Text to type',
            self::SELECT => 'Option value',
            self::PRESS => 'Button text',
            self::CLICK_LINK => 'Link text',
            default => null,
        };
    }

    public function getSelectorLabel(): ?string
    {
        return match ($this) {
            self::TYPE => 'Field (name attribute or CSS selector)',
            self::SELECT => 'Field (name attribute or CSS selector)',
            self::CHECK, self::UNCHECK => 'Field (name attribute or CSS selector)',
            self::CLICK => 'CSS selector',
            default => null,
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])->toArray();
    }
}
