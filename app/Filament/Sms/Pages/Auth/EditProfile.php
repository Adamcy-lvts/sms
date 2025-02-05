<?php

namespace App\Filament\Sms\Pages\Auth;

use Throwable;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Filament\Support\Exceptions\Halt;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Illuminate\Validation\Rules\Password;
use Filament\Support\Facades\FilamentView;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use function Filament\Support\is_app_url;
use Illuminate\Support\Facades\Storage;

class EditProfile extends BaseEditProfile
{
    protected static string $view = 'filament.sms.pages.auth.edit-profile';

    public function mount(): void
    {
        parent::mount();

        // Get the staff record and fill the signature if it exists
        $staff = auth()->user()->staff;
        if ($staff) {
            $this->form->fill([
                'first_name' => $staff->first_name,
                'last_name' => $staff->last_name,
                'email' => $staff->email,
                'signature' => $staff->signature
            ]);
        }
    }

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
                                $this->getPasswordFormComponent(),
                                $this->getPasswordConfirmationFormComponent(),
                                $this->getSignatureFormComponent(),
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
            ->disabled()
            ->maxLength(255);
    }

    protected function getLastNameFormComponent(): Component
    {
        return TextInput::make('last_name')
            ->label('Last Name')
            ->disabled()
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

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::pages/auth/edit-profile.form.password.label'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->rule(Password::default())
            ->autocomplete('new-password')
            ->dehydrated(fn($state): bool => filled($state))
            ->dehydrateStateUsing(fn($state): string => Hash::make($state))
            ->live(debounce: 500)
            ->same('passwordConfirmation');
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label(__('filament-panels::pages/auth/edit-profile.form.password_confirmation.label'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->visible(fn(Get $get): bool => filled($get('password')))
            ->dehydrated(false);
    }


    protected function getSignatureFormComponent(): Component
    {
        $school = auth()->user()->schools->first();
        return FileUpload::make('signature')
            //only show for staff with can_sign_reports_card_permission
            // ->visible(fn(Get $get) => auth()->user()->staff && auth()->user()->staff->can_sign_reports_card)
            ->image()
            ->disk('public')
            ->directory("{$school->slug}/staff_signatures") // Organize by school slug
            ->imageEditor() // Allow basic image editing
            ->maxSize(1024) // 1MB limit
            ->imageResizeMode('force')
            ->imageCropAspectRatio('5:2') // Good ratio for signatures
            ->columnSpanFull();
    }

    protected function mutateFormDataBeforeSave($data): array
    {
        // Get the signature value from form data
        $signature = $data['signature'] ?? null;
        unset($data['signature']);

        // Update staff signature if user has a staff record
        if ($staff = auth()->user()->staff) {
            // If signature is null/empty and there was a previous signature, delete the old file
            if (empty($signature) && $staff->signature) {
                Storage::disk('public')->delete($staff->signature);
            }
            
            // Update the staff record with new signature value (can be null)
            $staff->update(['signature' => $signature]);
        }

        return $data;
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeSave($data);

            $this->callHook('beforeSave');

            $this->handleRecordUpdate($this->getUser(), $data);

            $this->callHook('afterSave');

            $this->commitDatabaseTransaction();
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction() ?
                $this->rollBackDatabaseTransaction() :
                $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        // Handle password session
        if (request()->hasSession() && array_key_exists('password', $data)) {
            request()->session()->put([
                'password_hash_' . Filament::getAuthGuard() => $data['password'],
            ]);
        }

        $this->data['password'] = null;
        $this->data['passwordConfirmation'] = null;

        $this->getSavedNotification()?->send();


        if ($redirectUrl = $this->getRedirectUrl()) {
            $this->redirect($redirectUrl, navigate: FilamentView::hasSpaMode() && is_app_url($redirectUrl));
        }
    }
}
