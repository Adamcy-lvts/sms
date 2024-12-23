<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use App\Models\Bank;
use Filament\Tables;
use App\Models\Staff;
use App\Models\Status;
use App\Models\Subject;
use App\Models\Teacher;
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
use Illuminate\Support\Facades\Hash;
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
    protected static ?string $navigationGroup = 'Staff Management';
    protected static ?int $navigationSort = 1;
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
                                            // $tenant = Filament::getTenant();

                                            // Get the tenant's settings
                                            // $settings = $tenant->getSettingsAttribute();

                                            // Get current designation_id if exists
                                            // $designationId = request()->get('designation_id');

                                            // Create generator instance with tenant settings
                                            // $generator = new EmployeeIdGenerator($settings);

                                            // Generate ID with proper format
                                            // return $generator->generate([
                                            //     'id_format' => $settings->employee_id_format_type,
                                            //     'designation_id' => $designationId,
                                            // ]);
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
                                    ->preload()
                                    ->required(),

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
                                                Forms\Components\Select::make('roles')
                                                    ->multiple()
                                                    ->options(fn() => \Spatie\Permission\Models\Role::pluck('name', 'id'))
                                                    ->preload()
                                                    ->searchable(),

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

                                        Forms\Components\Select::make('permissions')
                                            ->multiple()
                                            ->options(fn() => \Spatie\Permission\Models\Permission::pluck('name', 'id'))
                                            ->preload()
                                            ->searchable()
                                            ->columnSpanFull(),

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
                                    ->visible(fn(Forms\Get $get): bool => $get('create_user_account')),
                            ])
                            ->columns(1)
                            ->collapsible(),

                        Forms\Components\Section::make('Access Settings Preview')
                            ->schema([
                                Forms\Components\Placeholder::make('login_email')
                                    ->label('Login Email')
                                    ->content(fn(Forms\Get $get): string => $get('email') ?? 'Not set'),

                                Forms\Components\Placeholder::make('assigned_roles')
                                    ->label('Assigned Roles')
                                    ->content(function (Forms\Get $get) {
                                        $roleIds = $get('roles') ?? [];
                                        $roleNames = \Spatie\Permission\Models\Role::whereIn('id', $roleIds)->pluck('name');
                                        return $roleNames->join(', ') ?: 'No roles assigned';
                                    }),

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
                            ->visible(fn(Forms\Get $get): bool => $get('create_user_account'))
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
                    ->circular()
                    ->defaultImageUrl(fn($record) => asset('images/default-avatar.png')),

                Tables\Columns\TextColumn::make('employee_id')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Employee ID copied')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('full_name')
                    ->searchable(['first_name', 'last_name', 'middle_name'])
                    ->sortable()
                    ->description(fn(Staff $record): string => $record->email),

                Tables\Columns\TextColumn::make('designation.name')
                    ->badge()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_teacher')
                    ->boolean()
                    ->label('Teacher')
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

                Tables\Columns\TextColumn::make('salary')
                    ->money('NGN')
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

                Tables\Filters\SelectFilter::make('bank')
                    ->relationship('bank', 'name')
                    ->preload()
                    ->multiple()
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\Action::make('viewProfile')
                    ->url(fn(Staff $record): string => route('filament.sms.resources.staff.view', ['record' => $record, 'tenant' => Filament::getTenant()]))
                    ->icon('heroicon-m-eye'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
