<?php

namespace App\Filament\Sms\Pages\Auth;

use App\Models\Lga;
use App\Models\Bank;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Agent;
use App\Models\State;
use App\Models\School;
use App\Models\Status;
use App\Models\Subject;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\ClassRoom;
use App\Models\Designation;
use App\Models\PaymentType;
use Illuminate\Support\Str;
use App\Models\GradingScale;
use App\Models\PaymentMethod;
use App\Models\AssessmentType;
use App\Models\Permission;
use App\Models\SchoolSettings;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Log;
use App\Services\SchoolSetupService;
use Filament\Events\Auth\Registered;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Radio;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Illuminate\Support\Facades\Blade;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Illuminate\Validation\Rules\Password;
use Filament\Forms\Components\Placeholder;
use Illuminate\Contracts\Auth\Authenticatable;
use Filament\Pages\Auth\Register as AuthRegister;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use App\Models\Staff;
use Filament\Forms\Components\Checkbox;
use App\Services\LegalDocumentService;

class Register extends AuthRegister
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.sms.pages.auth.register';

    public ?array $data = [];
    public $referralCode;

    protected string $userModel;
    public $planId;
    public $billing;
    // public LegalDocumentService $legalDocs;

    // lets' add a boot method to set the user model
    // public function boot(LegalDocumentService $legalDocs): void
    // {
    //     $this->legalDocs = $legalDocs;
    // }


    // Also update the mount method:
    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->callHook('beforeFill');
        $this->referralCode = request()->query('ref');
        $this->planId = request()->query('plan');
        $this->billing = request()->query('billing');
        $this->form->fill();
        $this->callHook('afterFill');
    }

    protected function mutateFormDataBeforeRegister(array $data): array
    {
        return $data;
    }

    public function form(Form $form): Form
    {
        $legalDocs = new LegalDocumentService();

        return $form
            ->schema([
                Wizard::make([
                    // Step 1: Basic School Information
                    Wizard\Step::make('School Information')
                        ->icon('heroicon-o-academic-cap')
                        ->description('Enter your school\'s basic details')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('name')
                                    ->label('School Name')
                                    ->required()
                                    ->placeholder('e.g. Kings Private School')
                                    ->unique(School::class, 'name')
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn(Set $set, ?string $state) => $set('slug', Str::slug($state))),

                                TextInput::make('name_ar')
                                    ->label('School Name in Arabic (Optional)')
                                    ->placeholder('اسم المدرسة بالعربية'),
                            ]),

                            Grid::make(2)->schema([
                                Select::make('school_type')
                                    ->label('School Type')
                                    ->native(false)
                                    ->options([
                                        'nursery' => 'Nursery School',
                                        'primary' => 'Primary School',
                                        'secondary' => 'Secondary School',
                                        'primary_secondary' => 'Primary & Secondary',
                                        'all' => 'Nursery, Primary & Secondary'
                                    ])
                                    ->required(),

                                Select::make('ownership_type')
                                    ->native(false)
                                    ->label('Ownership Type')
                                    ->options([
                                        'private' => 'Private',
                                        'public' => 'Public/Government',
                                        'religious' => 'Religious/Faith-based',
                                        'community' => 'Community',
                                        'ngo' => 'NGO/Non-Profit'
                                    ])
                                    ->required(),
                            ]),

                            // Grid::make(2)->schema([
                            //     TextInput::make('registration_number')
                            //         ->label('School Registration Number')
                            //         ->placeholder('Government issued registration number')
                            //         ->helperText('Official registration/license number from education ministry'),

                            //     Select::make('language_of_instruction')
                            //         ->label('Primary Language of Instruction')
                            //         ->options([
                            //             'english' => 'English',
                            //             'arabic' => 'Arabic',
                            //             'bilingual' => 'Bilingual (English & Arabic)'
                            //         ])
                            //         ->required(),
                            // ]),

                            // Grid::make(2)->schema([
                            //     Select::make('calendar_type')
                            //         ->label('Academic Calendar')
                            //         ->options([
                            //             'regular' => 'Regular (Sep-Jul)',
                            //             'international' => 'International (Aug-Jun)',
                            //             'islamic' => 'Islamic Calendar'
                            //         ])
                            //         ->required()
                            //         ->helperText('Choose your preferred academic calendar'),

                            //     Select::make('gender_type')
                            //         ->label('Student Gender Type')
                            //         ->options([
                            //             'mixed' => 'Mixed/Co-educational',
                            //             'boys' => 'Boys Only',
                            //             'girls' => 'Girls Only'
                            //         ])
                            //         ->required(),
                            // ]),

                            FileUpload::make('logo')
                                ->directory('school-logos'),
                        ]),

                    // Step 2: Contact Information
                    Wizard\Step::make('Contact Details')
                        ->icon('heroicon-o-phone')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('email')
                                    ->label('Official Email')
                                    ->email()
                                    ->required()
                                    ->unique(School::class),

                                TextInput::make('phone')
                                    ->label('Primary Contact Number')
                                    ->tel()
                                    ->required(),
                            ]),

                            Textarea::make('address')
                                ->label('Physical Address')
                                ->required()
                                ->rows(3),

                            Grid::make(2)->schema([
                                Select::make('state_id')
                                    ->label('State')
                                    ->native(false)
                                    ->searchable()
                                    ->options(State::pluck('name', 'id'))
                                    ->required()
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn(Set $set) => $set('lga_id', null)),

                                Select::make('lga_id')
                                    ->native(false)
                                    ->searchable()
                                    ->label('Local Government')
                                    ->options(fn(Get $get) => Lga::where('state_id', $get('state_id'))->pluck('name', 'id'))
                                    ->required(),
                            ]),
                        ]),

                    // Step 3: Administrative Account
                    Wizard\Step::make('Admin Account')
                        ->icon('heroicon-o-user')
                        ->description('Create the principal/admin account')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('first_name')->required(),
                                TextInput::make('last_name')->required(),
                            ]),

                            TextInput::make('admin_email')
                                ->label('Admin Email')
                                ->email()
                                ->required()
                                ->unique('users', 'email'),

                            Grid::make(2)->schema([
                                TextInput::make('password')
                                    ->password()
                                    ->required()
                                    ->rule(Password::default())
                                    ->dehydrateStateUsing(fn($state) => Hash::make($state))
                                    ->same('passwordConfirmation'),

                                TextInput::make('passwordConfirmation')
                                    ->password()
                                    ->required()
                                    ->dehydrated(false),
                            ]),


                        ]),

                    // Step 4: Academic Settings
                    Wizard\Step::make('Academic Settings')
                        ->icon('heroicon-o-cog')
                        ->schema([
                            Toggle::make('create_classes')
                                ->label('Create Classes')
                                ->helperText('Automatically create class structure')
                                ->default(true)
                                ->live(),

                            Grid::make(3)
                                ->schema([
                                    Toggle::make('create_nursery')
                                        ->label('Nursery Classes')
                                        ->default(false)
                                        ->visible(fn(Get $get) => $get('create_classes')),

                                    Toggle::make('create_primary')
                                        ->label('Primary Classes')
                                        ->default(false)
                                        ->visible(fn(Get $get) => $get('create_classes')),

                                    Toggle::make('create_secondary')
                                        ->label('Secondary Classes')
                                        ->default(false)
                                        ->visible(fn(Get $get) => $get('create_classes')),
                                ]),

                            Grid::make(2)
                                ->schema([
                                    Select::make('class_sections')
                                        ->label('Sections per Class')
                                        ->native(false)
                                        ->options([
                                            'A' => 'A only',
                                            'AB' => 'A and B',
                                            'ABC' => 'A, B and C'
                                        ])
                                        ->visible(fn(Get $get) => $get('create_classes'))
                                        ->default('A'),

                                    TextInput::make('class_capacity')
                                        ->label('Default Class Capacity')
                                        ->numeric()
                                        ->default(40)
                                        ->visible(fn(Get $get) => $get('create_classes')),
                                ]),

                            // Subject creation toggles

                            Section::make('Subjects Setup')
                                ->schema([
                                    Grid::make(2)->schema([
                                        Toggle::make('create_subjects')
                                            ->label('Create Regular Subjects')
                                            ->helperText('Create standard academic subjects')
                                            ->default(true)
                                            ->live(),

                                        Toggle::make('create_islamic_subjects')
                                            ->label('Create Islamic Subjects')
                                            ->helperText('Add Islamic and Arabic subjects')
                                            ->default(false)
                                            ->visible(fn(Get $get) => $get('create_subjects')),
                                    ]),
                                ]),

                            Checkbox::make('terms_accepted')
                                ->label(new HtmlString('I accept the <a href="' . $legalDocs->getTermsUrl() . '" class="text-primary-600 hover:text-primary-500" target="_blank">Terms of Service</a> and <a href="' . $legalDocs->getPrivacyUrl() . '" class="text-primary-600 hover:text-primary-500" target="_blank">Privacy Policy</a>'))
                                ->required()
                                ->rules(['accepted'])
                                ->columnSpanFull(),

                        ]),
                ])
                    ->submitAction(new HtmlString(Blade::render(<<<BLADE
                        <x-filament::button type="submit" size="sm">
                            Create School Account
                        </x-filament::button>
                    BLADE)))
                    ->persistStepInQueryString('step'),
            ])
            ->statePath('data');
    }


    public function register(): ?RegistrationResponse
    {
        // Add terms validation
        $this->validate([
            'data.terms_accepted' => 'accepted',
        ], [
            'data.terms_accepted.accepted' => 'You must accept the Terms of Service and Privacy Policy to continue.',
        ]);

        try {
            $this->rateLimit(2);

            $this->callHook('beforeValidate');
            $data = $this->form->getState();
            $this->callHook('afterValidate');

            // Add plan and billing data from URL
            $data['plan_id'] = $this->planId;
            $data['billing_type'] = $this->billing;

            DB::beginTransaction();
            try {
                $this->callHook('beforeRegister');

                // Create school
                $school = $this->createSchool($data);

                // Create user
                $user = $this->createUser($data, $school);
                $school->members()->attach($user->id);

                // Setup school configurations using the service
                $setupService = new SchoolSetupService();


                // Continue
                $setupService->setup($school, $data, $user);

                // After school setup, run shield command programmatically
                Artisan::call('shield:super-admin', [
                    '--user' => $user->id,
                    '--tenant' => $school->id
                ]);

                $superAdmin = $school->getSuperAdmin();;

                $superAdmin->givePermissionTo(Permission::all());

                // Handle referral if exists
                if ($this->referralCode) {
                    $this->handleReferral($school);
                }

                $this->callHook('afterRegister');

                DB::commit();

                // Handle post-registration tasks
                event(new Registered($user));
                // $this->sendEmailVerificationNotification($user);
                Filament::auth()->login($user);
                session()->regenerate();

                return app(RegistrationResponse::class);
            } catch (\Exception $e) {
                Log::error('Registration error:', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                DB::rollBack();
                if (isset($data['logo'])) {
                    Storage::disk('public')->delete($data['logo']);
                }
                throw $e;
            }
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/register.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(__('filament-panels::pages/auth/register.notifications.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable / 60,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->danger()
                ->send();

            return null;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Registration failed')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }


    // Keep only the essential methods
    protected function createSchool(array $data): School
    {
        $school = School::create([
            'name' => $data['name'],
            'name_ar' => $data['name_ar'] ?? null,
            'slug' => Str::slug($data['name']),
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'state_id' => $data['state_id'],
            'lga_id' => $data['lga_id'],
            'logo' => $data['logo'] ?? null,
        ]);

        return $school;
    }

    protected function createUser(array $data, School $school): Authenticatable
    {
        // Get or create active status
        $activeStatus = Status::where('type', 'user')->where('name', 'active')->first();

        $user = $this->getUserModel()::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['admin_email'],
            'password' => $data['password'],
            'status_id' =>  $activeStatus->id,
        ]);


        // $user->assignRole($schoolRole);

        return $user;
    }

    protected function handleReferral(School $school): void
    {
        $agent = Agent::where('referral_code', $this->referralCode)->first();
        if ($agent) {
            $school->update(['agent_id' => $agent->id]);
        }
    }
}
