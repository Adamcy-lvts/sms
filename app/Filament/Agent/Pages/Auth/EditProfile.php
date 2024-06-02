<?php

namespace App\Filament\Agent\Pages\Auth;

use Exception;
use Filament\Pages\Page;
use Filament\Tables\Table;
use App\Models\Subscription;
use Filament\Facades\Filament;
use Filament\Tables\Actions\Action;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Pages\Auth\EditProfile as ProfileEdit;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EditProfile extends ProfileEdit
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.agent.pages.auth.edit-profile';

    public $user;

    public function mount(): void
    {
        $this->fillForm();

        $this->user = $this->getUser();

    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getFirstNameFormComponent(),
                        $this->getLastNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPhoneFormComponent(),
                        $this->getBusinessNameFormComponent(),
                        $this->getAccountNumberFormComponent(),
                        $this->getAccountNameFormComponent(),
                        $this->getBankFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->operation('edit')
                    ->model($this->getUser())
                    ->statePath('data')
                    ->inlineLabel(!static::isSimple()),
            ),
        ];
    }

    protected function getFirstNameFormComponent(): Component
    {
        return TextInput::make('first_name')
            ->label(__('First Name'))
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getLastNameFormComponent(): Component
    {
        return TextInput::make('last_name')
            ->label(__('Last Name'))
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::pages/auth/edit-profile.form.email.label'))
            ->email()
            ->required()
            ->maxLength(255)
            ->unique(ignoreRecord: true);
    }

    protected function getPhoneFormComponent(): Component
    {
        return TextInput::make('phone')
            ->label(__('Phone'))
            ->maxLength(255);
    }

    protected function getBusinessNameFormComponent(): Component
    {
        return TextInput::make('business_name')
            ->label(__('Business Name'))
            ->required()
            ->maxLength(255);
    }

    protected function getAccountNumberFormComponent(): Component
    {
        return TextInput::make('account_number')
            ->label(__('Account Number'))
            ->numeric()
            ->required()
            ->maxLength(20);
    }

    protected function getAccountNameFormComponent(): Component
    {
        return TextInput::make('account_name')
            ->label(__('Account Name'))
            ->required()
            ->maxLength(255);
    }

    protected function getBankFormComponent(): Component
    {
        return TextInput::make('bank')
            ->label(__('Bank'))
            ->required()
            ->maxLength(255);
    }

    public function getUser(): Authenticatable & Model
    {
        $user = Filament::auth()->user();

        if (!$user instanceof Model) {
            throw new Exception('The authenticated user object must be an Eloquent model to allow the profile page to update it.');
        }

        return $user;
    }

    protected function fillForm(): void
    {
        $user = $this->getUser();
        $agent = $user->agent; // Assuming there's a relationship defined as `agent` in the User model

        $data = array_merge(
            $user->attributesToArray(),
            $agent ? $agent->attributesToArray() : []
        );

        $this->callHook('beforeFill');
        $data = $this->mutateFormDataBeforeFill($data);
        $this->form->fill($data);
        $this->callHook('afterFill');
    }


    // protected function fillForm(): void
    // {
    //     $data = $this->getUser()->attributesToArray();

    //     $this->callHook('beforeFill');

    //     $data = $this->mutateFormDataBeforeFill($data);

    //     $this->form->fill($data);

    //     $this->callHook('afterFill');
    // }
}
