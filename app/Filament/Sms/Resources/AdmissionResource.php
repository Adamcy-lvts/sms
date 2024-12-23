<?php

namespace App\Filament\Sms\Resources;

use App\Models\Lga;
use Filament\Forms;
use Filament\Tables;
use App\Models\State;
use App\Models\Status;
use App\Helpers\Gender;
use App\Models\Student;
use Filament\Forms\Get;
use App\Helpers\Options;
use Filament\Forms\Form;
use App\Models\Admission;
use App\Models\ClassRoom;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Illuminate\Support\Facades\Blade;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Infolists\Components\Group;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\AdmissionResource\Pages;
use App\Filament\Sms\Resources\AdmissionResource\Pages\NewStudent;
use App\Filament\Sms\Resources\AdmissionResource\RelationManagers;
use App\Filament\Sms\Resources\StudentResource\Pages\CreateStudent;

class AdmissionResource extends Resource
{
    protected static ?string $model = Admission::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationGroup = 'Admissions Management';
    protected static ?int $navigationSort = 1;

    // public static function canViewAny(): bool
    // {
    //     return Filament::getTenant()->hasFeature('Admission Management');
    // }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Wizard::make([
                    Wizard\Step::make('Personal Information')
                        ->columns(2)
                        ->schema([

                            Fieldset::make('Personal Information')
                                ->schema([
                                    Forms\Components\TextInput::make('admission_number')
                                        ->maxLength(255)
                                        ->unique(ignorable: fn($record) => $record)
                                        ->placeholder('Will be auto-generated')
                                        ->disabled()
                                        ->dehydrated(),

                                    Forms\Components\Select::make('academic_session_id')
                                        ->relationship(name: 'academicSession', titleAttribute: 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required(),

                                    Forms\Components\TextInput::make('first_name')
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('Enter first name'),

                                    Forms\Components\TextInput::make('last_name')
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('Enter last name'),

                                    Forms\Components\TextInput::make('middle_name')
                                        ->maxLength(255)
                                        ->placeholder('Enter middle name (optional)'),

                                    Forms\Components\DatePicker::make('date_of_birth')
                                        ->native(false)
                                        ->required()
                                        ->maxDate(now()->subYears(2))
                                        ->displayFormat('d/m/Y'),

                                    Forms\Components\Select::make('gender')
                                        ->options(Options::gender())
                                        ->required()
                                        ->native(false),

                                    Forms\Components\TextInput::make('phone_number')
                                        ->tel()
                                        ->maxLength(255)
                                        ->placeholder('+234 XXX XXX XXXX'),

                                    Forms\Components\TextInput::make('email')
                                        ->email()
                                        ->maxLength(255)
                                        ->unique(ignorable: fn($record) => $record),

                                    Forms\Components\Select::make('state_id')
                                        ->options(State::all()->pluck('name', 'id')->toArray())
                                        ->live()
                                        ->searchable()
                                        ->preload()
                                        ->label('State of Origin')
                                        ->required(),

                                    Forms\Components\Select::make('lga_id')
                                        ->options(fn(Forms\Get $get): Collection => Lga::query()
                                            ->where('state_id', $get('state_id'))
                                            ->pluck('name', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->label('Local Government Area'),
                                ]),

                            FormSection::make('Photo')
                                ->schema([
                                    Forms\Components\FileUpload::make('passport_photograph')
                                        ->label('Passport Photograph')
                                        ->image()
                                        ->maxSize(2048)
                                        ->disk('public')
                                        ->directory('admission_passport')
                                        ->columnSpanFull(),
                                ]),


                        ]),

                    Wizard\Step::make('Personal Information 2')
                        ->schema([
                            Fieldset::make('Personal Infomration 2')
                                ->schema([
                                    Forms\Components\Textarea::make('address')
                                        ->required()
                                        ->maxLength(255)->columnSpan(2),
                                    Forms\Components\Select::make('religion')->options(Options::religion()),
                                    Forms\Components\Select::make('blood_group')->options(Options::bloodGroup()),
                                    Forms\Components\Select::make('genotype')->options(Options::genotype()),
                                    Forms\Components\Select::make('type')->options(Options::disability())->live()->label('Disability')
                                        ->afterStateUpdated(fn(Select $component) => $component
                                            ->getContainer()
                                            ->getComponent('dynamicTypeFields')
                                            ->getChildComponentContainer()
                                            ->fill()),

                                    FormSection::make()
                                        ->schema(fn(Get $get): array => match ($get('type')) {
                                            'Yes' => [
                                                Forms\Components\TextInput::make('disability_type')
                                                    ->required()
                                                    ->maxLength(255),
                                                Forms\Components\Textarea::make('disability_description')
                                                    ->required()
                                                    ->maxLength(255)->columnSpan(2),
                                            ],
                                            default => [],
                                        })->key('dynamicTypeFields'),

                                    Forms\Components\TextInput::make('previous_school_name')
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('previous_class')
                                        ->maxLength(255),
                                    Forms\Components\DatePicker::make('admitted_date')->native(false)
                                        ->required(),
                                    Forms\Components\DatePicker::make('application_date')->native(false),

                                    Forms\Components\Select::make('status_id')->options(Status::where('type', 'admission')->pluck('name', 'id')->toArray())->label('Status')
                                        ->required(),
                                ])
                        ]),

                    Wizard\Step::make('Guardian/Parent Information')
                        ->schema([
                            Fieldset::make('Guardian/Parent Information')
                                ->schema([
                                    Forms\Components\TextInput::make('guardian_name')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('guardian_relationship')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('guardian_phone_number')
                                        ->tel()
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('guardian_email')
                                        ->email()
                                        ->maxLength(255),
                                    Forms\Components\Textarea::make('guardian_address')
                                        ->maxLength(255)->columnSpan(2),
                                ]),
                        ]),
                    Wizard\Step::make('Emergency Contact Information')
                        ->schema([
                            Fieldset::make('Personal Infomration 2')
                                ->schema([
                                    Forms\Components\TextInput::make('emergency_contact_name')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('emergency_contact_relationship')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('emergency_contact_phone_number')
                                        ->tel()
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('emergency_contact_email')
                                        ->email()
                                        ->maxLength(255),
                                ]),

                        ]),
                ])->skippable()->persistStepInQueryString()->submitAction(new HtmlString(Blade::render(<<<BLADE
                <x-filament::button
                    type="submit"
                    size="sm"
                >
                    Submit
                </x-filament::button>
            BLADE))),

            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('passport_photograph')
                    ->circular()
                    ->defaultImageUrl(url('/img/default.jpg'))
                    ->label('Photo')
                    ->size(40),

                TextColumn::make('admission_number')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('full_name')
                    ->searchable(['first_name', 'last_name', 'middle_name'])
                    ->sortable(),

                TextColumn::make('academicSession.name')
                    ->label('Session')
                    ->sortable(),

                // Add Application Date
                TextColumn::make('application_date')
                    ->label('Applied On')
                    ->date('M d, Y')
                    ->sortable(),

                // Add Admission Date
                TextColumn::make('admitted_date')
                    ->label('Admitted On')
                    ->date('M d, Y')
                    ->sortable(),

                // Add Guardian name for reference
                TextColumn::make('guardian_name')
                    ->label('Guardian')
                    ->searchable()
                    ->toggleable(true)
                    ->description(fn($record) => $record->guardian_phone_number),

                TextColumn::make('status.name')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('academic_session_id')
                    ->relationship('academicSession', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Academic Session'),

                SelectFilter::make('status_id')
                    ->label('Status')
                    ->options(Status::where('type', 'admission')->pluck('name', 'id'))
                    ->multiple()
                    ->preload(),

                SelectFilter::make('gender')
                    ->options(Options::gender())
                    ->multiple(),

                SelectFilter::make('state_id')
                    ->relationship('state', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->label('State'),

                Filter::make('age_range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('age_from')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->placeholder('From'),

                                Forms\Components\TextInput::make('age_to')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->placeholder('To'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['age_from'],
                                fn(Builder $query, $age): Builder => $query->where('date_of_birth', '<=', now()->subYears($age))
                            )
                            ->when(
                                $data['age_to'],
                                fn(Builder $query, $age): Builder => $query->where('date_of_birth', '>=', now()->subYears($age + 1))
                            );
                    }),

                Filter::make('admission_date')
                    ->form([
                        Forms\Components\DatePicker::make('admitted_from')
                            ->label('From')
                            ->native(false),
                        Forms\Components\DatePicker::make('admitted_until')
                            ->label('Until')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['admitted_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('admitted_date', '>=', $date)
                            )
                            ->when(
                                $data['admitted_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('admitted_date', '<=', $date)
                            );
                    }),

                TernaryFilter::make('has_disability')
                    ->label('Disability Status')
                    ->placeholder('All students')
                    ->trueLabel('Has disability')
                    ->falseLabel('No disability')
                    ->queries(
                        true: fn(Builder $query) => $query->whereNotNull('disability_type'),
                        false: fn(Builder $query) => $query->whereNull('disability_type'),
                    ),

                Filter::make('contact_info')
                    ->label('Contact Information')
                    ->form([
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel(),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['phone'],
                                fn(Builder $query, $phone): Builder => $query->where('phone_number', 'like', "%{$phone}%")
                            )
                            ->when(
                                $data['email'],
                                fn(Builder $query, $email): Builder => $query->where('email', 'like', "%{$email}%")
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Action::make('enroll')
                        ->icon('heroicon-o-academic-cap')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Enroll Student')
                        ->modalDescription(fn(Admission $record) => "Enroll {$record->full_name} as a student")
                        ->form([
                            Section::make('Class Assignment')
                                ->description('Assign student to a class')
                                ->schema([
                                    Select::make('class_room_id')
                                        ->label('Class')
                                        ->options(fn() => ClassRoom::where('school_id', Filament::getTenant()->id)
                                            ->orderBy('name')
                                            ->pluck('name', 'id'))
                                        ->required()
                                        ->searchable(),

                                    Select::make('status_id')
                                        ->label('Student Status')
                                        ->options(fn() => Status::where('type', 'student')
                                            ->where('name', 'active')
                                            ->pluck('name', 'id'))
                                        ->required()
                                        ->default(fn() => Status::where('type', 'student')
                                            ->where('name', 'active')
                                            ->first()?->id),

                                    Forms\Components\TextInput::make('identification_number')
                                        ->label('Student ID')
                                        ->placeholder('Optional - Will be auto-generated if empty')
                                        ->maxLength(255),

                                    Forms\Components\Toggle::make('create_user_account')
                                        ->label('Create User Account')
                                        ->helperText('Create a user account for student portal access')
                                        ->default(false),
                                ])
                        ])
                        ->visible(
                            fn(Admission $record): bool =>
                            $record->status?->name === 'approved' || $record->status?->name === 'processing' &&
                                !$record->student()->exists()
                        )
                        ->action(function (Admission $record, array $data): void {
                            DB::beginTransaction();

                            try {
                                // Create the student record
                                $student = Student::create([
                                    'school_id' => Filament::getTenant()->id,
                                    'admission_id' => $record->id,
                                    'class_room_id' => $data['class_room_id'],
                                    'status_id' => $data['status_id'],
                                    'first_name' => $record->first_name,
                                    'last_name' => $record->last_name,
                                    'middle_name' => $record->middle_name,
                                    'date_of_birth' => $record->date_of_birth,
                                    'phone_number' => $record->phone_number,
                                    'profile_picture' => $record->passport_photograph,
                                    'identification_number' => $data['identification_number'] ?? $record->admission_number,
                                    'admission_number' => $record->admission_number,
                                    'created_by' => Auth::id(),
                                ]);

                                // Optional: Create user account if requested
                                if ($data['create_user_account'] && $record->email) {
                                    $user = \App\Models\User::create([
                                        'first_name' => $record->first_name,
                                        'last_name' => $record->last_name,
                                        'email' => $record->email,
                                        'password' => bcrypt('password'), // You might want to generate a random password
                                    ]);

                                    $student->update(['user_id' => $user->id]);

                                    // TODO: Send welcome email with credentials
                                }

                                // Update admission status to enrolled
                                $enrolledStatus = Status::where('type', 'admission')
                                    ->where('name', 'enrolled')
                                    ->first();

                                if ($enrolledStatus) {
                                    $record->update(['status_id' => $enrolledStatus->id]);
                                }

                                DB::commit();

                                Notification::make()
                                    ->success()
                                    ->title('Student Enrolled Successfully')
                                    ->body("Student {$student->full_name} has been enrolled")
                                    ->persistent()
                                    ->actions([
                                        \Filament\Notifications\Actions\Action::make('view')
                                            ->button()
                                            ->url(fn() => route('filament.sms.resources.students.view', [
                                                'tenant' => Filament::getTenant()->slug,
                                                'record' => $student->id,
                                            ]))
                                    ])
                                    ->send();
                            } catch (\Exception $e) {
                                DB::rollBack();

                                Notification::make()
                                    ->danger()
                                    ->title('Error Enrolling Student')
                                    ->body('There was an error enrolling the student. Please try again.')
                                    ->send();

                                throw $e;
                            }
                        }),

                    Action::make('review')
                        ->icon('heroicon-o-document-magnifying-glass')
                        ->color('warning')
                        ->label('Review Admission')
                        ->modalWidth('4xl')
                        ->modalHeading(fn(Admission $record) => "Review Admission: {$record->full_name}")
                        ->visible(
                            fn(Admission $record): bool =>
                            in_array($record->status?->name, ['pending', 'processing'])
                        )
                        ->modalContent(function (Admission $record): InfoList {
                            return InfoList::make()
                                ->record($record)
                                ->schema([
                                    InfolistSection::make('Personal Information')
                                        ->columns(3)
                                        ->schema([
                                            ImageEntry::make('passport_photograph')
                                                ->label('Photo')
                                                ->circular()
                                                ->columnSpan(1),

                                            Group::make()
                                                ->columnSpan(2)
                                                ->columns(2)
                                                ->schema([
                                                    TextEntry::make('full_name')
                                                        ->label('Full Name')
                                                        ->weight('bold'),
                                                    TextEntry::make('gender')
                                                        ->badge()
                                                        ->color(fn(string $state): string => match ($state) {
                                                            'male' => 'info',
                                                            'female' => 'danger',
                                                            default => 'gray',
                                                        }),
                                                    TextEntry::make('date_of_birth')
                                                        ->label('Date of Birth')
                                                        ->date(),
                                                    TextEntry::make('age')
                                                        ->state(function (Admission $record): string {
                                                            if (!$record->date_of_birth) {
                                                                return 'N/A';
                                                            }
                                                            $birthDate = \Carbon\Carbon::parse($record->date_of_birth);
                                                            $age = max(0, $birthDate->age);
                                                            return $age . ' years';

                                                            
                                                        }),
                                                ]),
                                        ]),

                                    InfolistSection::make('Contact & Location')
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make('phone_number')
                                                ->label('Phone')
                                                ->icon('heroicon-m-phone'),
                                            TextEntry::make('email')
                                                ->icon('heroicon-m-envelope'),
                                            TextEntry::make('address')
                                                ->columnSpanFull(),
                                            TextEntry::make('state.name')
                                                ->label('State of Origin'),
                                            TextEntry::make('lga.name')
                                                ->label('LGA'),
                                        ]),

                                    InfolistSection::make('Academic Information')
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make('academicSession.name')
                                                ->label('Session'),
                                            TextEntry::make('admission_number')
                                                ->label('Admission Number')
                                                ->copyable(),
                                            TextEntry::make('previous_school_name')
                                                ->label('Previous School'),
                                            TextEntry::make('previous_class')
                                                ->label('Previous Class'),
                                            TextEntry::make('admitted_date')
                                                ->label('Admission Date')
                                                ->date(),
                                        ]),

                                    InfolistSection::make('Guardian Information')
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make('guardian_name')
                                                ->label('Guardian Name'),
                                            TextEntry::make('guardian_relationship')
                                                ->label('Relationship'),
                                            TextEntry::make('guardian_phone_number')
                                                ->label('Guardian Phone')
                                                ->icon('heroicon-m-phone'),
                                            TextEntry::make('guardian_email')
                                                ->label('Guardian Email')
                                                ->icon('heroicon-m-envelope'),
                                            TextEntry::make('guardian_address')
                                                ->label('Guardian Address')
                                                ->columnSpanFull(),
                                        ]),

                                    InfolistSection::make('Emergency Contact')
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make('emergency_contact_name')
                                                ->label('Emergency Contact'),
                                            TextEntry::make('emergency_contact_relationship')
                                                ->label('Relationship'),
                                            TextEntry::make('emergency_contact_phone_number')
                                                ->label('Emergency Phone')
                                                ->icon('heroicon-m-phone'),
                                        ]),

                                    InfolistSection::make('Additional Information')
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make('religion'),
                                            TextEntry::make('blood_group')
                                                ->label('Blood Group'),
                                            TextEntry::make('genotype'),
                                            TextEntry::make('disability_type')
                                                ->visible(fn($record) => !empty($record->disability_type)),
                                            TextEntry::make('disability_description')
                                                ->visible(fn($record) => !empty($record->disability_description))
                                                ->columnSpanFull(),
                                        ]),
                                ]);
                        })
                        ->form([
                            Section::make('Review Decision')
                                ->description('Update admission status and provide feedback')
                                ->schema([
                                    Select::make('new_status_id')
                                        ->label('Update Status')
                                        ->options(fn() => Status::where('type', 'admission')
                                            ->whereIn('name', ['approved', 'rejected', 'processing'])
                                            ->pluck('name', 'id'))
                                        ->required()
                                        ->live()
                                        ->native(false),

                                    Textarea::make('review_notes')
                                        ->label('Review Notes')
                                        ->placeholder('Add any notes or feedback about this admission')
                                        ->rows(3),

                                    Grid::make(2)
                                        ->schema([
                                            Toggle::make('send_notification')
                                                ->label('Send Email Notification')
                                                ->default(true)
                                                ->visible(
                                                    fn(Get $get) =>
                                                    Status::find($get('new_status_id'))?->name === 'approved'
                                                ),

                                            Toggle::make('enroll_immediately')
                                                ->label('Enroll After Approval')
                                                ->default(false)
                                                ->visible(
                                                    fn(Get $get) =>
                                                    Status::find($get('new_status_id'))?->name === 'approved'
                                                ),
                                        ])
                                ])
                        ])
                        ->closeModalByClickingAway(false)
                        ->modalFooterActions([
                            Action::make('approve_and_enroll')
                                ->label('Approve & Enroll')
                                ->color('success')
                                ->icon('heroicon-o-academic-cap')
                                ->visible(
                                    fn(Admission $record) =>
                                    in_array($record->status?->name, ['pending', 'processing'])
                                )
                                ->action(function (Admission $record) {
                                    DB::beginTransaction();

                                    try {
                                        // Set status to approved
                                        $approvedStatus = Status::where('type', 'admission')
                                            ->where('name', 'approved')
                                            ->first();

                                        $record->update([
                                            'status_id' => $approvedStatus->id,
                                        ]);

                                        // Auto-assign to first available class
                                        $defaultClass = ClassRoom::where('school_id', Filament::getTenant()->id)
                                            ->orderBy('name')
                                            ->first();

                                        // Create student record
                                        $student = Student::create([
                                            'school_id' => Filament::getTenant()->id,
                                            'admission_id' => $record->id,
                                            'class_room_id' => $defaultClass->id,
                                            'status_id' => Status::where('type', 'student')
                                                ->where('name', 'active')
                                                ->first()?->id,
                                            'first_name' => $record->first_name,
                                            'last_name' => $record->last_name,
                                            'middle_name' => $record->middle_name,
                                            'date_of_birth' => $record->date_of_birth,
                                            'phone_number' => $record->phone_number,
                                            'profile_picture' => $record->passport_photograph,
                                            'admission_number' => $record->admission_number,
                                            'created_by' => Auth::id(),
                                        ]);

                                        DB::commit();

                                        Notification::make()
                                            ->success()
                                            ->title('Admission Approved & Student Enrolled')
                                            ->body("Successfully processed admission for {$record->full_name}")
                                            ->persistent()
                                            ->actions([
                                                \Filament\Notifications\Actions\Action::make('view')
                                                    ->button()
                                                    ->url(fn() => StudentResource::getUrl('view', [
                                                        'tenant' => Filament::getTenant()->slug,
                                                        'record' => $student->id,
                                                    ]))
                                            ])
                                            ->send();
                                    } catch (\Exception $e) {
                                        DB::rollBack();

                                        Notification::make()
                                            ->danger()
                                            ->title('Error Processing Admission')
                                            ->body('There was an error processing the admission. Please try again.')
                                            ->send();

                                        throw $e;
                                    }
                                }),

                            Action::make('reject')
                                ->label('Reject')
                                ->color('danger')
                                ->icon('heroicon-o-x-circle')
                                ->requiresConfirmation()
                                ->modalHeading('Reject Admission')
                                ->modalDescription(fn(Admission $record) => "Are you sure you want to reject {$record->full_name}'s admission?")
                                ->visible(
                                    fn(Admission $record) =>
                                    in_array($record->status?->name, ['pending', 'processing'])
                                )
                                ->action(function (Admission $record) {
                                    $rejectedStatus = Status::where('type', 'admission')
                                        ->where('name', 'rejected')
                                        ->first();

                                    $record->update([
                                        'status_id' => $rejectedStatus->id
                                    ]);

                                    Notification::make()
                                        ->warning()
                                        ->title('Admission Rejected')
                                        ->body("Admission for {$record->full_name} has been rejected")
                                        ->send();
                                }),

                            Action::make('need_more_info')
                                ->label('Need More Info')
                                ->color('warning')
                                ->icon('heroicon-o-clock')
                                ->visible(
                                    fn(Admission $record) =>
                                    in_array($record->status?->name, ['pending', 'processing'])
                                )
                                ->action(function (Admission $record) {
                                    $processingStatus = Status::where('type', 'admission')
                                        ->where('name', 'processing')
                                        ->first();

                                    $record->update([
                                        'status_id' => $processingStatus->id
                                    ]);

                                    Notification::make()
                                        ->info()
                                        ->title('Status Updated')
                                        ->body("Admission marked as needing more information")
                                        ->send();
                                }),
                        ])
                        ->action(function (Admission $record, array $data): void {
                            $record->update([
                                'status_id' => $data['new_status_id'],
                                'review_notes' => $data['review_notes'] ?? null,
                            ]);

                            if ($data['enroll_immediately'] ?? false) {
                                // Trigger enrollment process
                                // ... enrollment logic here
                            }

                            if ($data['send_notification'] ?? false) {
                                // Send email notification
                                // ... notification logic here 
                            }

                            Notification::make()
                                ->success()
                                ->title('Admission Reviewed')
                                ->body("Successfully updated status for {$record->full_name}")
                                ->send();
                        }),
                    Tables\Actions\ViewAction::make()
                        ->label('View Admission Letter')
                        ->icon('heroicon-o-document-text')
                        ->modalContent(null) // Disable default modal
                        ->url(
                            fn(Admission $record) =>
                            route('filament.sms.resources.admissions.view', [
                                'record' => $record,
                                'tenant' => Filament::getTenant()->slug
                            ])
                        )
                        ->openUrlInNewTab()
                        ->visible(
                            fn(Admission $record): bool =>
                            $record->status?->name === 'approved'
                        ),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),


            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $approvedStatus = Status::where('type', 'admission')
                                ->where('name', 'approved')
                                ->first();

                            $records->each(function ($record) use ($approvedStatus) {
                                if ($record->status->name === 'pending') {
                                    $record->update(['status_id' => $approvedStatus->id]);
                                }
                            });

                            Notification::make()
                                ->success()
                                ->title('Selected Admissions Approved')
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdmissions::route('/'),
            'create' => Pages\CreateAdmission::route('/create'),
            'edit' => Pages\EditAdmission::route('/{record}/edit'),
            'view' => Pages\ViewAdmissionLetter::route('/{record}'),
            'newstudent' => Pages\NewStudent::route('{record}/new-student'),

        ];
    }
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where(
            'status_id',
            Status::where('type', 'admission')
                ->where('name', 'pending')
                ->first()?->id
        )->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'warning' : null;
    }
}
