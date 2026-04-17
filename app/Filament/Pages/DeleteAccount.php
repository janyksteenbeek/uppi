<?php

namespace App\Filament\Pages;

use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DeleteAccount extends Page implements HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-trash';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.delete-account';

    public ?array $data = [];

    public bool $isPasswordFilled = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('current_password')
                    ->label('Current Password')
                    ->password()
                    ->required()
                    ->rules(['required'])
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->isPasswordFilled = ! empty($state);
                    })
                    ->helperText('Please enter your current password to confirm account deletion.'),
            ])
            ->statePath('data');
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->label('Delete Account')
            ->color('danger')
            ->disabled(fn () => ! $this->isPasswordFilled)
            ->requiresConfirmation()
            ->modalHeading('We are about to delete your account')
            ->modalDescription('Are you sure you want to delete your account? All your data, including monitors, alerts, and settings will be permanently deleted. This is the final confirmation before your account is deleted. This action is irreversible.')
            ->modalSubmitActionLabel('Yes, delete my account')
            ->action(function () {
                $data = $this->form->getState();

                if (! Hash::check($data['current_password'], auth()->user()->password)) {
                    Notification::make()
                        ->title('Incorrect password')
                        ->danger()
                        ->send();

                    return;
                }

                $user = auth()->user();
                $user->monitors()->delete();
                $user->alerts()->delete();
                $user->statusPages()->delete();
                $user->tokens()->delete();
                $user->checks()->delete();
                Auth::logout();
                $user->delete();

                Notification::make()
                    ->title('Account deleted successfully')
                    ->success()
                    ->send();

                return redirect('/');
            });
    }
}
