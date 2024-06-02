<?php

namespace App\Filament\Agent\Pages\Auth;

use App\Models\Bank;
use App\Models\User;
use App\Models\Agent;
use App\Models\Course;
use Filament\Pages\Page;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Select;
use App\Jobs\CreatePaystackSubaccount;
use Illuminate\Auth\Events\Registered;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Validation\Rules\Password;
use Illuminate\Contracts\Support\Htmlable;
use Unicodeveloper\Paystack\Facades\Paystack;
use Filament\Pages\Auth\Register as AuthRegister;
use Filament\Http\Responses\Auth\RegistrationResponse;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;


class Register extends AuthRegister
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.agent.pages.auth.register';

    public ?array $data = [];

    protected string $userModel;

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->form->fill();
    }

    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/register.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(array_key_exists('body', __('filament-panels::pages/auth/register.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/register.notifications.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]) : null)
                ->danger()
                ->send();

            return null;
        }

        $data = $this->form->getState();

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'user_type' => 'agent',
            'password' => Hash::make($data['password']),
        ]);

        $bank = Bank::find($data['bank']);

        $agent = Agent::create([
            'user_id' => $user->id,
            'business_name' => $data['business_name'],
            'account_number' => $data['account_number'],
            'account_name' => $data['account_name'],
            'bank_id' => $bank->id,
        ]);

        // Prepare data for the subaccount
        $subaccountData = [
            'business_name' => $data['business_name'],
            'settlement_bank' => $data['bank'],
            'account_number' => $data['account_number'],
            'percentage_charge' => 80,
            'primary_contact_email' => $data['email'],
        ];

        // Dispatch the job to create the subaccount
        dispatch(new CreatePaystackSubaccount($agent, $subaccountData));

        // $this->sendEmailVerificationNotification($user);

        Filament::auth()->login($user);

        session()->regenerate();

        return app(RegistrationResponse::class);
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getBusinessNameFormComponent()->columnSpan('full'),
                        $this->getFirstNameFormComponent(),
                        $this->getLastNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPhoneFormComponent(),
                        $this->getAccountNumberFormComponent(),
                        $this->getAccountNameFormComponent(),
                        $this->getBankFormComponent()->columnSpan('full'),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])->columns(2)
                    ->statePath('data'),
            ),
        ];
    }

    protected function getBusinessNameFormComponent(): Component
    {
        return TextInput::make('business_name')
            ->helperText('Business, Organisation, Company name etc. If any.')
            ->label(__('Business Name'))
            ->maxLength(255)
            ->autofocus();
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
            ->label(__('filament-panels::pages/auth/register.form.email.label'))
            ->email()
            ->required()
            ->maxLength(255)
            ->unique($this->getUserModel());
    }

    protected function getPhoneFormComponent(): Component
    {
        return TextInput::make('phone')
            ->label(__('Phone'))
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getAccountNumberFormComponent(): Component
    {
        return TextInput::make('account_number')
            ->label(__('Account Number'))
            ->numeric()
            ->inputMode('decimal')
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getAccountNameFormComponent(): Component
    {
        return TextInput::make('account_name')
            ->label(__('Account Name'))
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getBankFormComponent(): Component
    {
        return Select::make('bank')
            ->label('Bank')
            ->required()
            ->options(Bank::all()->pluck('name', 'code')) // This will make the bank code the value and bank name the label
            ->searchable();
    }


    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::pages/auth/register.form.password.label'))
            ->password()
            ->required()
            ->rule(Password::default())
            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
            ->same('passwordConfirmation')
            ->validationAttribute(__('filament-panels::pages/auth/register.form.password.validation_attribute'));
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label(__('filament-panels::pages/auth/register.form.password_confirmation.label'))
            ->password()
            ->required()
            ->dehydrated(false);
    }

    public function getTitle(): string | Htmlable
    {
        return __('Agent Registrations');
    }

    public function getHeading(): string | Htmlable
    {
        return __('Agent Registration');
    }
}
