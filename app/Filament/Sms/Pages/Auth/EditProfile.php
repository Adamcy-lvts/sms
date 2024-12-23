<?php

namespace App\Filament\Sms\Pages\Auth;

use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class EditProfile extends BaseEditProfile
{
    protected static string $view = 'filament.sms.pages.auth.edit-profile';

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        Section::make()
                            ->schema([
                                $this->getFirstNameFormComponent(),
                                $this->getLastNameFormComponent(),
                                $this->getEmailFormComponent(),
                            ])
                            ->columns(2),
                    ])
                    ->operation('edit')
                    ->model($this->getUser())
                    ->statePath('data'),
            ),
        ];
    }

    protected function getFirstNameFormComponent(): Component
    {
        return TextInput::make('first_name')
            ->label('First Name')
            ->required()
            ->maxLength(255);
    }

    protected function getLastNameFormComponent(): Component
    {
        return TextInput::make('last_name')
            ->label('Last Name')
            ->required()
            ->maxLength(255);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Email Address')
            ->email()
            ->required()
            ->maxLength(255)
            ->unique(ignoreRecord: true);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Profile updated successfully';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('changePassword')
                ->label('Change Password')
                ->icon('heroicon-o-key')
                ->action(function () {
                    // Handle password change
                }),
            
            Action::make('enable2FA')
                ->label('Setup 2FA')
                ->icon('heroicon-o-shield-check')
                ->action(function () {
                    // Handle 2FA setup
                }),
        ];
    }
}