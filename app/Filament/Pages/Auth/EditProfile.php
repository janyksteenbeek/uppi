<?php

namespace App\Filament\Pages\Auth;

use Carbon\CarbonTimeZone;
use Filament\Forms\Form;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getTimezoneFormComponent(),
            ]);
    }

    protected function getTimezoneFormComponent(): Component
    {
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
            ->in(CarbonTimeZone::listIdentifiers());
    }
}
