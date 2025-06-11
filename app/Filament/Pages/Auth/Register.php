<?php

namespace App\Filament\Pages\Auth;

use Carbon\CarbonTimeZone;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;


class Register extends \Filament\Pages\Auth\Register
{
    /**
     * @return array<int | string, string | Form>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        $this->getTimezoneFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getTimezoneFormComponent(): Component
    {
        $defaultTimezone = config('app.timezone') ?? 'UTC';

        return Select::make('timezone')
            ->options(
                collect(CarbonTimeZone::listIdentifiers())
                    ->mapWithKeys(function ($timezone) {
                        return [$timezone => $timezone];
                    })
                    ->toArray()
            )
            ->searchable()
            ->optionsLimit(450)
            ->required()
            ->in(CarbonTimeZone::listIdentifiers())
            ->default($defaultTimezone)
            ->extraAlpineAttributes(['x-init' => '$wire.set("data.timezone", Intl.DateTimeFormat().resolvedOptions().timeZone)']);
    }
}
