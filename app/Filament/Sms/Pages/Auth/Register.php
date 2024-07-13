<?php

namespace App\Filament\Sms\Pages\Auth;

use App\Models\Agent;
use App\Models\School;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use Illuminate\Support\HtmlString;
use Filament\Events\Auth\Registered;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Wizard;
use Illuminate\Support\Facades\Blade;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Illuminate\Validation\Rules\Password;
use Filament\Pages\Auth\Register as AuthRegister;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;

class Register extends AuthRegister
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.sms.pages.auth.register';

    public ?array $data = [];
    public $referralCode;

    protected string $userModel;

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->callHook('beforeFill');

        $this->referralCode = request()->query('ref');

        $this->form->fill();

        $this->callHook('afterFill');
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



        $user = $this->wrapInDatabaseTransaction(function () {
            $this->callHook('beforeValidate');

            $data = $this->form->getState();
            $this->callHook('afterValidate');

            // Separate user and school data right from the beginning
            $schoolData = [
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'email' => $data['email'],
                'address' => $data['address'],
                'phone' => $data['phone']
            ];

            // Assume `mutateFormDataBeforeRegister` is properly separating user data
            $userData = $this->mutateFormDataBeforeRegister([
                'first_name' => $data['first_name'], // Adjust these keys according to your actual form fields
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $this->callHook('beforeRegister');

            $school = School::create($schoolData);
            // $userData['school_id'] = $school->id; // Append school_id to user data after school creation
            $user = $this->handleRegistration($userData);

            $school->members()->attach($user);

            // If a referral code is present, link the user to the agent
            if ($this->referralCode) {
                $agent = Agent::where('referral_code', $this->referralCode)->first();
                if ($agent) {
                    // Attach the agent to the user using the pivot table
                    $school->update(['agent_id' => $agent->id]);
                }
            }

            $this->form->model($user)->saveRelationships();
            $this->callHook('afterRegister');

            return $user;
        });

        event(new Registered($user));
        $this->sendEmailVerificationNotification($user);
        Filament::auth()->login($user);
        session()->regenerate();

        return app(RegistrationResponse::class);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(array $data): Model
    {
        return $this->getUserModel()::create($data);
    }


    public function form(Form $form): Form
    {
        return $form;
    }

    /**
     * @return array<int | string, string | Form>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        Wizard::make([
                            Wizard\Step::make('School Information')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('School Name')
                                        ->required(),

                                    TextInput::make('email')
                                        ->label(__('School Email'))
                                        ->email()
                                        ->required()
                                        ->maxLength(255)
                                        ->unique($this->getUserModel()),
                                    Textarea::make('address')
                                        ->label('School Address')
                                        ->required(),
                                    TextInput::make('phone')
                                        ->label('Phone Number')
                                        ->required(),
                                    FileUpload::make('logo')
                                        ->directory('school-logos'),
                                ]),
                            Wizard\Step::make('Admin Details')
                                ->schema([
                                    TextInput::make('first_name')
                                        ->label('First Name')
                                        ->required(),
                                    TextInput::make('last_name')
                                        ->label('Last Name')
                                        ->required(),
                                    TextInput::make('password')
                                        ->label(__('filament-panels::pages/auth/register.form.password.label'))
                                        ->password()
                                        ->revealable(filament()->arePasswordsRevealable())
                                        ->required()
                                        ->rule(Password::default())
                                        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                                        ->same('passwordConfirmation')
                                        ->validationAttribute(__('filament-panels::pages/auth/register.form.password.validation_attribute')),

                                    TextInput::make('passwordConfirmation')
                                        ->label(__('filament-panels::pages/auth/register.form.password_confirmation.label'))
                                        ->password()
                                        ->revealable(filament()->arePasswordsRevealable())
                                        ->required()
                                        ->dehydrated(false),
                                ]),
                            Wizard\Step::make('Confirmation')
                                ->description('Review and submit your information.')
                                ->schema([
                                    // Add a summary or any additional fields if needed
                                ])

                        ])->submitAction(new HtmlString(Blade::render(<<<BLADE
                                                        <x-filament::button type="submit" size="sm">
                                                          Submit
                                                        </x-filament::button>
                                                    BLADE)))->skippable()
                            ->startOnStep(1)->persistStepInQueryString('wizard-step'),
                    ])->statePath('data'),
            ),
        ];
    }
}
