<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use App\Models\Bank;
use Filament\Tables;
use App\Models\Staff;
use App\Models\Status;
use App\Models\Subject;
use App\Models\Teacher;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Helpers\Options;
use Filament\Forms\Form;
use App\Models\ClassRoom;
use Filament\Tables\Table;
use App\Models\Designation;
use Filament\Support\RawJs;
use App\Settings\AppSettings;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use App\Helpers\EmployeeIdFormats;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Services\EmployeeIdGenerator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Sms\Resources\StaffResource\Pages;

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'School Management';
    protected static ?string $recordTitleAttribute = 'full_name';

    public static function form(Form $form): Form
    {
        $school = Filament::getTenant();
        return $form->schema([
            Forms\Components\Wizard::make([
                Forms\Components\Wizard\Step::make('Personal & Contact Information')
                    ->icon('heroicon-o-user')
                    ->description('Basic personal and contact details')
                    ->schema([
                        // Personal Information Section
                        Forms\Components\Section::make('Personal Information')
                            ->schema([
                                Forms\Components\FileUpload::make('profile_picture')
                                    ->label('Profile Photo')
                                    ->image()
                                    ->imageEditor()
                                    ->imageEditorAspectRatios(['1:1'])
                                    ->directory('staff-photos')
                                    ->columnSpanFull(),

                                Forms\Components\Group::make([
                                    Forms\Components\TextInput::make('employee_id')
                                        ->label('Employee ID')
                                        ->default(function () {
                                            // Get the current tenant
                                            $tenant = Filament::getTenant();

                                            // Get the tenant's settings
                                            $settings = $tenant->getSettingsAttribute();

                                            // Get current designation_id if exists
                                            $designationId = request()->get('designation_id');

                                            // Create generator instance with tenant settings
                                            $generator = new EmployeeIdGenerator($settings);

                                            // Generate ID with proper format
                                            return $generator->generate([
                                                'id_format' => $settings->employee_id_format_type,
                                                'designation_id' => $designationId,
                                            ]);
                                        })
                                        ->disabled()
                                        ->dehydrated()
                                        ->required()
                                        ->helperText('Employee ID will be generated automatically based on your settings.')
                                ])->columns(1),
                                Forms\Components\TextInput::make('first_name')
                                    ->required()
                                    ->string()
                                    ->prefixIcon('heroicon-m-user')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('last_name')
                                    ->required()
                                    ->string()
                                    ->prefixIcon('heroicon-m-user')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('middle_name')
                                    ->string()
                                    ->prefixIcon('heroicon-m-user')
                                    ->maxLength(255),

                                Forms\Components\Select::make('designation_id')
                                    ->options(fn() => Designation::pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\Select::make('gender')
                                    ->options(Options::gender())
                                    ->required()
                                    ->native(false),

                                Forms\Components\DatePicker::make('date_of_birth')
                                    ->label('Date of Birth')
                                    ->required()
                                    ->maxDate(now()->subYears(18))
                                    ->displayFormat('d/m/Y')
                                    ->native(false),

                                Forms\Components\Select::make('status_id')
                                    ->options(fn() => Status::where('type', 'staff')->pluck('name', 'id'))
                                    ->required()
                                    ->preload()
                                    ->label('Employment Status'),

                                Forms\Components\FileUpload::make('signature')
                                    ->image()
                                    ->disk('public')
                                    ->directory("{$school->slug}/staff_signatures") // Organize by school slug
                                    ->imageEditor() // Allow basic image editing
                                    ->maxSize(1024) // 1MB limit
                                    ->imageResizeMode('force')
                                    ->imageCropAspectRatio('5:2') // Good ratio for signatures
                                    // ->imageResizeTargetWidth('400') // Reasonable width for signatures
                                    // ->imageResizeTargetHeight('200')
                                    ->columnSpanFull(),


                            ])
                            ->columns(2),

                        // Contact Information Section
                        Forms\Components\Section::make('Contact Information')
                            ->schema([
                                Forms\Components\TextInput::make('phone_number')
                                    ->tel()
                                    ->required()
                                    ->prefixIcon('heroicon-m-phone')
                                    ->maxLength(255),


                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->prefixIcon('heroicon-m-envelope')
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),

                                Forms\Components\Textarea::make('address')
                                    ->required()
                                    ->columnSpanFull(),

                            ])
                            ->columns(2),
                    ]),

                // Employment & Qualifications Combined Step
                Forms\Components\Wizard\Step::make('Employment & Qualifications')
                    ->icon('heroicon-o-briefcase')
                    ->description('Employment details and qualifications')
                    ->schema([
                        // Employment Details Section
                        Forms\Components\Section::make('Employment Details')
                            ->schema([
                                Forms\Components\DatePicker::make('hire_date')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y'),

                                Forms\Components\TextInput::make('salary')
                                    ->numeric()
                                    ->required()
                                    ->prefix('₦')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(','),

                                Forms\Components\Select::make('bank_id')
                                    ->options(fn() => Bank::pluck('name', 'id'))
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\TextInput::make('account_number')
                                    ->required()
                                    ->numeric()
                                    ->length(10),

                                Forms\Components\TextInput::make('account_name')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TagsInput::make('skills')
                                    ->separator(',')
                                    ->suggestions([
                                        'Teaching',
                                        'Administration',
                                        'Leadership',
                                        'Curriculum Development',
                                        'Student Counseling',
                                    ]),

                                Forms\Components\Textarea::make('job_description')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        // Qualifications Section
                        Forms\Components\Section::make('Qualifications')
                            ->schema([
                                Forms\Components\Repeater::make('qualifications')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Qualification Name')
                                            ->required(),
                                        Forms\Components\TextInput::make('institution')
                                            ->label('Institution')
                                            ->required(),
                                        Forms\Components\DatePicker::make('year_obtained')
                                            ->label('Year')
                                            ->format('Y')
                                            ->displayFormat('Y')
                                            ->required(),
                                        Forms\Components\FileUpload::make('documents')
                                            ->multiple()
                                            ->directory('qualification-documents')
                                            ->visibility('public')
                                            ->preserveFilenames()
                                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                                            ->maxSize(5120)
                                    ])
                                    ->defaultItems(1)
                                    ->collapsible()
                                    ->itemLabel(
                                        fn(array $state): ?string =>
                                        $state['name'] ?? 'New Qualification'
                                    )
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->reorderableWithButtons()
                                    ->cloneable()
                                    ->addActionLabel('Add Qualification')
                            ]),
                    ]),


                Forms\Components\Wizard\Step::make('Teaching Details')
                    ->icon('heroicon-o-academic-cap')
                    ->description('For teaching staff members')
                    ->schema([
                        Forms\Components\Toggle::make('is_teacher')
                            ->label('Register as Teacher')
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (!$state) {
                                    $set('teacher.subjects', null);
                                    $set('teacher.class_rooms', null);
                                }
                            }),

                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\Select::make('teacher.subjects')
                                    ->multiple()
                                    ->options(fn() => Subject::pluck('name', 'id'))
                                    ->preload()
                                    ->searchable(),

                                Forms\Components\Select::make('teacher.class_rooms')
                                    ->multiple()
                                    ->options(fn() => ClassRoom::pluck('name', 'id'))
                                    ->preload()
                                    ->searchable(),

                                Forms\Components\TextInput::make('teacher.specialization'),

                                Forms\Components\Textarea::make('teacher.teaching_experience')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->visible(fn(Forms\Get $get): bool => $get('is_teacher')),
                    ]),

                Forms\Components\Wizard\Step::make('Account Settings')
                    ->icon('heroicon-o-cog')
                    ->description('System access credentials')
                    ->schema([
                        Forms\Components\Section::make('User Account Settings')
                            ->schema([
                                Forms\Components\Toggle::make('create_user_account')
                                    ->label('Create System Access Account')
                                    ->helperText('Enable this to create login access for this staff member')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if (!$state) {
                                            $set('account_settings', null);
                                            $set('roles', null);
                                            $set('permissions', null);
                                        }
                                    }),

                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([

                                                Forms\Components\Select::make('default_password_type')
                                                    ->label('Default Password')
                                                    ->options([
                                                        'email' => 'Use Email Address',
                                                        'phone' => 'Use Phone Number',
                                                        'custom' => 'Set Custom Password',
                                                    ])
                                                    ->default('phone')
                                                    ->live()
                                                    ->required(),

                                                Forms\Components\TextInput::make('custom_password')
                                                    ->password()
                                                    ->confirmed()
                                                    ->visible(fn(Forms\Get $get) => $get('default_password_type') === 'custom')
                                                    ->required(fn(Forms\Get $get) => $get('default_password_type') === 'custom'),

                                                Forms\Components\TextInput::make('custom_password_confirmation')
                                                    ->password()
                                                    ->visible(fn(Forms\Get $get) => $get('default_password_type') === 'custom')
                                                    ->required(fn(Forms\Get $get) => $get('default_password_type') === 'custom'),
                                            ]),

                                        Forms\Components\Toggle::make('force_password_change')
                                            ->label('Force Password Change on First Login')
                                            ->default(true)
                                            ->inline(false),

                                        Forms\Components\Toggle::make('send_credentials')
                                            ->label('Send Login Credentials')
                                            ->helperText('Send login credentials via email to the staff member')
                                            ->default(true)
                                            ->inline(false),
                                    ])
                                    ->visible(fn(Forms\Get $get): bool => (bool) $get('create_user_account')),
                            ])
                            ->columns(1)
                            ->collapsible(),

                        Forms\Components\Section::make('Access Settings Preview')
                            ->schema([
                                Forms\Components\Placeholder::make('login_email')
                                    ->label('Login Email')
                                    ->content(fn(Forms\Get $get): string => $get('email') ?? 'Not set'),


                                Forms\Components\Placeholder::make('password_type')
                                    ->label('Password Type')
                                    ->content(function (Forms\Get $get) {
                                        $type = $get('default_password_type');
                                        return match ($type) {
                                            'email' => 'Email Address: ' . ($get('email') ?? 'Not set'),
                                            'phone' => 'Phone Number: ' . ($get('phone_number') ?? 'Not set'),
                                            'custom' => 'Custom Password',
                                            default => 'Not set'
                                        };
                                    }),
                            ])
                            ->columns(2)
                            ->visible(fn(Forms\Get $get): bool => (bool) $get('create_user_account'))
                            ->collapsed(false),
                    ]),
            ])
                ->skippable()
                ->columnSpanFull()  // Add this to each section
                ->persistStepInQueryString()
                ->submitAction(new HtmlString(Blade::render(<<<BLADE
                    <x-filament::button
                        type="submit"
                        size="sm"
                    >
                        Submit
                    </x-filament::button>
                BLADE)))
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('profile_picture_url')
                    ->label('Profile Photo')
                    ->circular()
                    ->defaultImageUrl(fn($record) => asset('images/default-avatar.png')),

                Tables\Columns\TextColumn::make('employee_id')
                    ->label('Employee ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Employee ID copied')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('full_name')
                    ->searchable(['first_name', 'last_name', 'middle_name'])
                    ->copyable()
                    ->sortable()
                    ->description(fn(Staff $record): string => $record->email),

                Tables\Columns\TextColumn::make('designation.name')
                    ->badge()
                    ->sortable(),

                // Add this new column
                Tables\Columns\TextColumn::make('user.status.name')
                    ->label('Account Status')
                    ->badge()
                    ->color(fn($record) => match ($record?->user?->status?->name) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'suspended' => 'warning',
                        'blocked' => 'danger',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn($record) => $record?->user?->status?->name ?? 'No Account')
                    ->description(fn($record) => $record?->user ? 'Has user account' : 'No user account')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('phone_number')
                    ->searchable()
                    ->toggleable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('status.name')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'resigned' => 'warning',
                        'suspended' => 'danger',
                        'terminated' => 'danger',
                        'deceased' => 'gray',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('hire_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),


            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_teacher')
                    ->label('Teacher Status')
                    ->placeholder('All Staff')
                    ->trueLabel('Teachers Only')
                    ->falseLabel('Non-Teachers Only')
                    ->queries(
                        true: fn(Builder $query) => $query->whereHas('teacher'),
                        false: fn(Builder $query) => $query->whereDoesntHave('teacher'),
                    ),

                Tables\Filters\SelectFilter::make('designation')
                    ->relationship('designation', 'name')
                    ->preload()
                    ->multiple()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('status')
                    ->relationship('status', 'name')
                    ->preload()
                    ->multiple(),

                // Tables\Filters\SelectFilter::make('bank')
                //     ->relationship('bank', 'name')
                //     ->preload()
                //     ->multiple()
                //     ->searchable(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // Add this new action before other actions
                    Tables\Actions\Action::make('generateEmployeeId')
                        ->icon('heroicon-o-identification')
                        ->visible(fn(Staff $record): bool => empty($record->employee_id))
                        ->modalHeading('Generate Employee ID')
                        ->form(function (Staff $record) {
                            // Check if there are any existing employee IDs
                            $hasExistingFormat = Staff::where('school_id', Filament::getTenant()->id)
                                ->whereNotNull('employee_id')
                                ->exists();

                            if ($hasExistingFormat) {
                                return [
                                    Forms\Components\Placeholder::make('format_info')
                                        ->content('Employee ID will be generated using the existing format.'),
                                ];
                            }

                            return [
                                Forms\Components\Select::make('format_type')
                                    ->label('ID Format Type')
                                    ->options([
                                        'basic' => 'Basic (EMP001)',
                                        'with_year' => 'With Year (EMP23001)',
                                        'department' => 'With Department (ADM23001)',
                                        'custom' => 'Custom Format'
                                    ])
                                    ->required()
                                    ->live()
                                    ->default('basic'),

                                Forms\Components\TextInput::make('custom_format')
                                    ->label('Custom Format')
                                    ->placeholder('EMP/{YY}/{NUM}')
                                    ->helperText('Available tokens: {PREFIX}, {YY}, {NUM}, {DEPT}')
                                    ->visible(fn(Get $get) => $get('format_type') === 'custom')
                                    ->required(fn(Get $get) => $get('format_type') === 'custom'),

                                Forms\Components\Select::make('prefix_type')
                                    ->label('Prefix Type')
                                    ->options([
                                        'default' => 'Default (EMP)',
                                        'school' => 'School Name',
                                        'custom' => 'Custom'
                                    ])
                                    ->default('default')
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        if ($state === 'school') {
                                            $schoolName = Filament::getTenant()->name;
                                            $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $schoolName), 0, 3));
                                            $set('prefix', $prefix);
                                        } elseif ($state === 'default') {
                                            $set('prefix', 'EMP');
                                        }
                                    }),

                                Forms\Components\TextInput::make('prefix')
                                    ->label('Default Prefix')
                                    ->default('EMP')
                                    ->required(),

                                Forms\Components\Select::make('year_format')
                                    ->label('Year Format')
                                    ->options([
                                        'none' => 'No Year',
                                        'short' => 'Short Year (23)',
                                        'full' => 'Full Year (2023)'
                                    ])
                                    ->default('short')
                                    ->visible(fn (Get $get) => in_array(
                                        $get('format_type'),
                                        ['with_year', 'with_department']
                                    )),

                                Forms\Components\TextInput::make('number_length')
                                    ->label('Sequential Number Length')
                                    ->numeric()
                                    ->default(3)
                                    ->required()
                                    ->minValue(1)
                                    ->maxValue(6),

                                Forms\Components\TextInput::make('separator')
                                    ->label('Separator Character')
                                    ->maxLength(1)
                                    ->default('-'),

                                Forms\Components\KeyValue::make('department_prefixes')
                                    ->label('Department Prefixes')
                                    ->keyLabel('Department')
                                    ->valueLabel('Prefix')
                                    ->reorderable()
                                    ->visible(fn(Get $get) => $get('format_type') === 'department'),
                            ];
                        })
                        ->action(function (Staff $record, array $data): void {
                            $tenant = Filament::getTenant();
                            $settings = $tenant->settings;

                            // Check if we should use existing format
                            $hasExistingFormat = Staff::where('school_id', $tenant->id)
                                ->whereNotNull('employee_id')
                                ->exists();

                            if (!$hasExistingFormat) {
                                // Update settings with new format preferences
                                $settings->employee_settings = $data;
                                $settings->save();
                            }

                            // Generate employee ID
                            $generator = new EmployeeIdGenerator($settings);
                            $employeeId = $generator->generate([
                                'designation_id' => $record->designation_id,
                            ]);

                            // Update staff record
                            $record->update(['employee_id' => $employeeId]);

                            Notification::make()
                                ->success()
                                ->title('Employee ID Generated')
                                ->body("Generated ID: {$employeeId}")
                                ->send();
                        }),

                    Tables\Actions\Action::make('viewProfile')
                        ->url(fn(Staff $record): string => route('filament.sms.resources.staff.view', ['record' => $record, 'tenant' => Filament::getTenant()]))
                        ->icon('heroicon-m-eye'),

                    Tables\Actions\Action::make('createUserAccount')
                        ->icon('heroicon-o-user-plus')
                        ->visible(fn(Staff $record): bool => !$record->user_id)
                        ->tooltip('Create user account for staff member')
                        ->modalWidth('md')
                        ->color('primary')
                        ->modalHeading('Create User Account')
                        ->form([
                            Forms\Components\Select::make('default_password_type')
                                ->label('Default Password')
                                ->options([
                                    'email' => 'Use Email Address',
                                    'phone' => 'Use Phone Number',
                                    'custom' => 'Set Custom Password',
                                ])
                                ->default('phone')
                                ->live()
                                ->required(),

                            Forms\Components\TextInput::make('custom_password')
                                ->password()
                                ->confirmed()
                                ->visible(fn(Forms\Get $get) => $get('default_password_type') === 'custom')
                                ->required(fn(Forms\Get $get) => $get('default_password_type') === 'custom'),

                            Forms\Components\TextInput::make('custom_password_confirmation')
                                ->password()
                                ->visible(fn(Forms\Get $get) => $get('default_password_type') === 'custom')
                                ->required(fn(Forms\Get $get) => $get('default_password_type') === 'custom'),


                            Forms\Components\Toggle::make('force_password_change')
                                ->label('Force Password Change on First Login')
                                ->default(true),

                            Forms\Components\Toggle::make('send_credentials')
                                ->label('Send Login Credentials')
                                ->helperText('Send login credentials via email')
                                ->default(true),
                        ])
                        ->action(function (Staff $record, array $data): void {
                            // Generate password based on selected type
                            $password = match ($data['default_password_type']) {
                                'email' => $record->email,
                                'phone' => $record->phone_number,
                                'custom' => $data['custom_password'],
                            };

                            $staffActiveStatus = Status::where('type', 'staff')->where('name', 'active')->first();

                            // Create user account
                            $user = $record->user()->create([
                                'first_name' => $record->first_name,
                                'last_name' => $record->last_name,
                                'middle_name' => $record->middle_name ?? null,
                                'user_type' => 'staff',
                                'status_id' => $staffActiveStatus->id,
                                'email' => $record->email,
                                'password' => Hash::make($password),
                                'force_password_change' => $data['force_password_change'],
                            ]);

                            // Update staff record with user_id
                            $record->update(['user_id' => $user->id]);

                            // Assign roles
                            if (!empty($data['roles'])) {
                                $user->roles()->sync($data['roles']);
                            }

                            // Attach user to school
                            $user->schools()->attach(Filament::getTenant()->id);

                            // Send credentials
                            if ($data['send_credentials']) {
                                try {
                                    app(Pages\CreateStaff::class)->sendLoginCredentials(
                                        staff: $record,
                                        password: $password
                                    );
                                } catch (\Exception $e) {
                                    Log::error('Failed to send credentials', [
                                        'staff_id' => $record->id,
                                        'error' => $e->getMessage()
                                    ]);
                                    Notification::make()
                                        ->warning()
                                        ->title('Could Not Send Credentials')
                                        ->body("Login credentials could not be sent via email. Please provide them manually.")
                                        ->send();
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('User Account Created')
                                ->body("User account has been created for {$record->full_name}")
                                ->send();
                        }),

                    // Add new action for managing user account status
                    Tables\Actions\Action::make('toggleUserStatus')
                        ->icon('heroicon-o-user-minus')
                        ->visible(fn(Staff $record): bool => (bool)$record->user_id)
                        ->requiresConfirmation()
                        ->modalWidth('md')
                        ->color(fn(Staff $record) => $record->user?->status?->name === 'active' ? 'danger' : 'success')
                        ->label(fn(Staff $record) => $record->user?->status?->name === 'active' ? 'Deactivate Account' : 'Activate Account')
                        ->modalHeading(fn(Staff $record) => $record->user?->status?->name === 'active' ? 'Deactivate User Account' : 'Activate User Account')
                        ->modalDescription(fn(Staff $record) => "Are you sure you want to " .
                            ($record->user?->status?->name === 'active' ? 'deactivate' : 'activate') .
                            " {$record->full_name}'s account?")
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Reason for Status Change')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->action(function (Staff $record, array $data): void {
                            $currentStatus = $record->user?->status?->name;

                            // Get the opposite status
                            $newStatus = Status::where('type', 'staff')
                                ->where('name', $currentStatus === 'active' ? 'inactive' : 'active')
                                ->first();

                            if (!$newStatus) {
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('Could not find appropriate status.')
                                    ->send();
                                return;
                            }

                            // Update user status
                            $record->user()->update([
                                'status_id' => $newStatus->id
                            ]);

                            // Log the status change
                            Log::info('User status changed', [
                                'staff_id' => $record->id,
                                'user_id' => $record->user_id,
                                'old_status' => $currentStatus,
                                'new_status' => $newStatus->name,
                                'reason' => $data['reason'],
                                'changed_by' => auth()->id()
                            ]);

                            Notification::make()
                                ->success()
                                ->title('Account Status Updated')
                                ->body("User account has been " . ($newStatus->name === 'active' ? 'activated' : 'deactivated'))
                                ->send();
                        }),

                    // Add this new action for managing roles
                    Tables\Actions\Action::make('manageRoles')
                        ->icon('heroicon-o-user-group')
                        ->visible(fn(Staff $record): bool => (bool)$record->user_id)
                        ->modalWidth('md')
                        ->color('primary')
                        ->label('Assign Roles')
                        ->modalHeading(fn(Staff $record) => "Assign Roles to {$record->full_name}")
                        ->form([
                            Forms\Components\CheckboxList::make('roles')
                                ->label('Assigned Roles')
                                ->options(fn() => \Spatie\Permission\Models\Role::where('team_id', Filament::getTenant()->id)->pluck('name', 'id'))
                                ->default(function (Staff $record) {
                                    return $record->user?->roles()->pluck('id')->toArray() ?? [];
                                })
                                ->searchable()
                                ->columns(2)
                                ->gridDirection('row')
                                ->helperText('Select the roles you want to assign to this staff member.')
                        ])
                        ->action(function (Staff $record, array $data): void {
                            if (!$record->user) {
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('Staff member must have a user account to assign roles.')
                                    ->send();
                                return;
                            }

                            try {
                                // Get the team/tenant ID
                                $teamId = Filament::getTenant()->id;

                                // Create the pivot data with team_id
                                $pivotData = array_fill(0, count($data['roles']), ['team_id' => $teamId]);
                                $roleData = array_combine($data['roles'], $pivotData);

                                // Sync roles with pivot data
                                $record->user->roles()->sync($roleData);

                                Log::info('Staff roles updated', [
                                    'staff_id' => $record->id,
                                    'user_id' => $record->user_id,
                                    'roles' => $data['roles'],
                                    'team_id' => $teamId,
                                    'updated_by' => auth()->id()
                                ]);

                                Notification::make()
                                    ->success()
                                    ->title('Roles Updated')
                                    ->body("Roles have been updated for {$record->full_name}")
                                    ->send();
                            } catch (\Exception $e) {
                                Log::error('Failed to update staff roles', [
                                    'staff_id' => $record->id,
                                    'error' => $e->getMessage()
                                ]);

                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('Failed to update roles. Please try again.')
                                    ->send();
                            }
                        }),

                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('updateStatus')
                        ->icon('heroicon-o-user')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Select::make('status_id')
                                ->label('New Status')
                                ->options(Status::where('type', 'staff')->pluck('name', 'id'))
                                ->required(),
                            Forms\Components\Textarea::make('reason')
                                ->label('Reason for Change')
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(function ($record) use ($data) {
                                $record->update([
                                    'status_id' => $data['status_id']
                                ]);

                                // activity()
                                //     ->performedOn($record)
                                //     ->withProperties([
                                //         'old_status' => $record->getOriginal('status_id'),
                                //         'new_status' => $data['status_id'],
                                //         'reason' => $data['reason']
                                //     ])
                                //     ->log('staff_status_changed');
                            });

                            Notification::make()
                                ->title('Status Updated')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('assignDesignation')
                        ->icon('heroicon-o-academic-cap')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Select::make('designation_id')
                                ->label('New Designation')
                                ->options(Designation::pluck('name', 'id'))
                                ->required(),
                            Forms\Components\DatePicker::make('effective_date')
                                ->label('Effective Date')
                                ->default(now())
                                ->required(),
                            Forms\Components\Textarea::make('notes')
                                ->label('Notes'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(function ($record) use ($data) {
                                $record->update([
                                    'designation_id' => $data['designation_id']
                                ]);

                                // activity()
                                //     ->performedOn($record)
                                //     ->withProperties([
                                //         'old_designation' => $record->getOriginal('designation_id'),
                                //         'new_designation' => $data['designation_id'],
                                //         'effective_date' => $data['effective_date'],
                                //         'notes' => $data['notes']
                                //     ])
                                //     ->log('staff_designation_changed');
                            });

                            Notification::make()
                                ->title('Designation Updated')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('exportSelected')
                        ->icon('heroicon-o-document-arrow-down')
                        ->label('Export Selected')
                        ->action(function (Collection $records) {
                            return response()->streamDownload(function () use ($records) {
                                echo $records->toCsv();
                            }, 'staff-export.csv');
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->poll('60s')
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\QualificationsRelationManager::class,
            // RelationManagers\SalaryHistoryRelationManager::class,
            // RelationManagers\DocumentsRelationManager::class,
            // RelationManagers\AttendanceRelationManager::class,
            // RelationManagers\LeaveRequestsRelationManager::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'first_name',
            'last_name',
            'middle_name',
            'email',
            'phone_number',
            'employee_id',
            'designation.name',
        ];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'ID' => $record->employee_id,
            'Designation' => $record->designation?->name,
            'Status' => $record->status?->name,
            'Phone' => $record->phone_number,
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return StaffResource::getUrl('view', ['record' => $record]);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaff::route('/'),
            'create' => Pages\CreateStaff::route('/create'),
            'edit' => Pages\EditStaff::route('/{record}/edit'),
            'view' => Pages\ViewStaff::route('/{record}/profile'),
        ];
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getNavigationLabel(): string
    {
        return __('Staff');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Staff Members');
    }

    public static function getModelLabel(): string
    {
        return __('Staff Member');
    }
}
