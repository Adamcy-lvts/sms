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
use Filament\Forms\Set;
use App\Helpers\Options;
use App\Models\Template;
use Filament\Forms\Form;
use App\Models\Admission;
use App\Models\ClassRoom;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use App\Models\AcademicSession;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Services\PdfGeneratorService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Illuminate\Support\Facades\Blade;
use Filament\Forms\Components\Section;
use App\Services\TemplateRenderService;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
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
use Filament\Forms\Components\Section as FormSection;
use App\Filament\Sms\Resources\AdmissionResource\Pages;
use Filament\Infolists\Components\Section as InfolistSection;
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
        $school = Filament::getTenant();
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
                                        ->disabled()
                                        ->prefixIcon('heroicon-m-information-circle')
                                        ->helperText('This is a preview of the admission number that will be assigned when approved')
                                        ->dehydrated(
                                            fn(Get $get): bool =>
                                            $get('status_id') && Status::find($get('status_id'))?->name === 'approved'
                                        ),

                                    Forms\Components\Select::make('academic_session_id')
                                        ->label('Academic Session')
                                        ->options(AcademicSession::pluck('name', 'id'))
                                        ->default(function () {
                                            return config('app.current_session')->id ?? null;
                                        }),

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
                                        ->directory("{$school->slug}/student_profile_pictures")
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
                                    Forms\Components\DatePicker::make('admitted_date')
                                        ->native(false)
                                        ->displayFormat('d F Y')
                                        ->visible(fn(Get $get) => $get('status_id') && Status::find($get('status_id'))?->name === 'approved')
                                        ->required(fn(Get $get) => $get('status_id') && Status::find($get('status_id'))?->name === 'approved')

                                        ->dehydrated(),
                                    Forms\Components\DatePicker::make('application_date')
                                        ->native(false)
                                        ->displayFormat('d F Y')
                                        ->required()

                                        ->dehydrated(),

                                    Forms\Components\Select::make('status_id')->options(Status::where('type', 'admission')
                                        ->pluck('name', 'id')->toArray())->label('Status')
                                        ->live()
                                        ->required()
                                        ->helperText(
                                            fn(Get $get): string =>
                                            Status::find($get('status_id'))?->name === 'approved'
                                                ? 'The admission number shown above will be officially assigned upon submission.'
                                                : ''
                                        )
                                        ->prefixIcon(
                                            fn(Get $get): string =>
                                            Status::find($get('status_id'))?->name === 'approved'
                                                ? 'heroicon-m-information-circle'
                                                : ''
                                        ),

                                    // Add new classroom field
                                    Forms\Components\Select::make('class_room_id')
                                        ->label('Class Room')
                                        ->options(fn() => ClassRoom::where('school_id', Filament::getTenant()->id)
                                            ->orderBy('name')
                                            ->pluck('name', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->required(
                                            fn(Get $get): bool =>
                                            $get('status_id') && Status::find($get('status_id'))?->name === 'approved'
                                        )
                                        ->visible(
                                            fn(Get $get): bool =>
                                            $get('status_id') && Status::find($get('status_id'))?->name === 'approved'
                                        ),
                                ])
                        ]),

                    Wizard\Step::make('Parent Information/Guardian')
                        ->schema([
                            Fieldset::make('Parent Information/Guardian')
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
                            Fieldset::make('Emergency Contact')
                                ->schema([
                                    // Add hint action to first field
                                    Forms\Components\TextInput::make('emergency_contact_name')
                                        ->required()
                                        ->maxLength(255)
                                        ->hintAction(
                                            \Filament\Forms\Components\Actions\Action::make('copyFromGuardian')
                                                ->label('Same as Guardian')
                                                ->icon('heroicon-m-document-duplicate')
                                                ->action(function (Set $set, Get $get) {
                                                    // Copy all guardian fields
                                                    $set('emergency_contact_name', $get('guardian_name'));
                                                    $set('emergency_contact_relationship', $get('guardian_relationship'));
                                                    $set('emergency_contact_phone_number', $get('guardian_phone_number'));
                                                    $set('emergency_contact_email', $get('guardian_email'));
                                                })
                                        ),

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
                                ])
                        ])
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
                    ->label('Parent/Guardian')
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
                    // Add this new action before existing actions
                    Action::make('generate_admission_number')
                        ->icon('heroicon-o-identification')
                        ->color('gray')
                        ->tooltip('Generate Admission Number')
                        ->visible(fn (Admission $record) => empty($record->admission_number))
                        ->requiresConfirmation()
                        ->modalDescription('This will generate a new admission number for this applicant.')
                        ->action(function (Admission $record) {
                            $admissionNumber = (new \App\Services\AdmissionNumberGenerator())->generate();
                            $record->update(['admission_number' => $admissionNumber]);

                            Notification::make()
                                ->success()
                                ->title('Admission Number Generated')
                                ->body("Generated admission number: {$admissionNumber}")
                                ->send();
                        }),

                    Action::make('enroll')
                        ->icon('heroicon-o-academic-cap')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Enroll Student')
                        ->modalDescription(fn(Admission $record) => "Enroll {$record->full_name} as a student")
                        ->visible(
                            fn(Admission $record): bool =>
                            $record->status?->name === 'approved' || $record->status?->name === 'pending' &&
                                !$record->student()->exists()
                        )
                        ->form([
                            Section::make('Class Assignment')
                                ->description('Assign student to a class')
                                ->schema([
                                    Select::make('class_room_id')
                                        ->label('Class Room')
                                        ->options(fn() => ClassRoom::where('school_id', Filament::getTenant()->id)
                                            ->orderBy('name')
                                            ->pluck('name', 'id'))
                                        ->required()
                                        ->searchable()
                                        ->native(false)  // Add this
                                        ->live(false),   // Add this


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
                                        ->default(fn(Admission $record) =>  $record->admission_number ? $record->admission_number : (new \App\Services\AdmissionNumberGenerator())->generate())
                                        ->disabled()
                                        ->dehydrated()
                                        ->placeholder('Optional - Will be auto-generated if empty')
                                        ->maxLength(255),

                                    Forms\Components\Toggle::make('create_user_account')
                                        ->label('Create User Account')
                                        ->helperText('Create a user account for student portal access')
                                        ->default(false),
                                ])
                        ])
                        ->modalWidth('lg')  // Add this
                        ->modalAlignment('center')  // Add this
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

                                //update admission number in admission table
                                $record->update(['admission_number' => $data['identification_number']]);

                                // Optional: Create user account if requested
                                if ($data['create_user_account'] && $record->email) {

                                    $user = $student->createUser();
                                    $student->update(['user_id' => $user->id]);

                                    // TODO: Send welcome email with credentials
                                }

                                // Update admission status to enrolled
                                $enrolledStatus = Status::where('type', 'admission')
                                    ->where('name', 'approved')
                                    ->first();

                                if ($enrolledStatus) {
                                    $record->update(['status_id' => $enrolledStatus->id]);
                                }



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

                                DB::commit();
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
                        ->url(
                            fn(Admission $record) =>
                            route('filament.sms.resources.admissions.view', [
                                'record' => $record,
                                'tenant' => Filament::getTenant()->slug
                            ])
                        ),
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
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Action::make('mail_letter')
                        ->icon('heroicon-m-envelope')
                        ->tooltip('Send Admission Letter')
                        ->requiresConfirmation()
                        ->modalDescription(fn(Admission $record) => "Send admission letter to {$record->full_name}'s email(s)")
                        ->visible(fn(Admission $record) => $record->status?->name === 'approved')
                        ->action(function (Admission $record): void {
                            try {

                                $school = Filament::getTenant();

                                $pdfService = app(PdfGeneratorService::class);
                                $template = Template::where('school_id', $school->id)
                                    ->where('category', 'admission_letter')
                                    ->where('is_active', true)
                                    ->first();

                                $renderer = new TemplateRenderService($template);
                                $content = $renderer->renderForAdmission($record);

                                $logoData = null;
                                if ($school->logo) {
                                    $logoPath = str_replace('public/', '', $school->logo);

                                    if (Storage::disk('public')->exists($logoPath)) {
                                        $fullLogoPath = Storage::disk('public')->path($logoPath);
                                        $extension = pathinfo($fullLogoPath, PATHINFO_EXTENSION);
                                        $logoData = 'data:image/' . $extension . ';base64,' . base64_encode(
                                            Storage::disk('public')->get($logoPath)
                                        );
                                    }
                                }

                                // Generate PDF with properly formatted filename
                                $pdfPath = $pdfService->generate(
                                    view: 'pdfs.admission-letter',
                                    data: [
                                        'content' => $content,
                                        'school' => $record->school,
                                        'admission' => $record,
                                        'logoData' => $logoData
                                    ],
                                    options: [
                                        'directory' => "{$record->school->slug}/documents/admissions",
                                        'filename' => "admission-letter-{$record->admission_number}-" . Str::slug($record->full_name) . '.pdf'
                                    ],
                                    save: true
                                );

                                // Collect valid emails
                                $emails = collect([
                                    $record->guardian_email => $record->guardian_name,
                                    $record->email => $record->full_name
                                ])
                                    ->filter(fn($name, $email) => filter_var($email, FILTER_VALIDATE_EMAIL))
                                    ->map(fn($name, $email) => new \Illuminate\Mail\Mailables\Address($email, $name));

                                if ($emails->isEmpty()) {
                                    Notification::make()
                                        ->warning()
                                        ->title('No Valid Email')
                                        ->body('No valid email address found for sending the letter.')
                                        ->send();
                                    return;
                                }

                                Mail::to($emails)
                                    ->queue(new \App\Mail\ApplicationApprovedMail(
                                        admission: $record,
                                        pdfPath: $pdfPath,
                                        pdfName: basename($pdfPath)
                                    ));

                                Notification::make()
                                    ->success()
                                    ->title('Letter Sent')
                                    ->body('Admission letter has been queued for sending.')
                                    ->send();
                            } catch (\Exception $e) {
                                Log::error('Failed to send admission letter', [
                                    'error' => $e->getMessage(),
                                    'admission_id' => $record->id
                                ]);

                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('Failed to send admission letter. Please try again.')
                                    ->send();
                            }
                        }),
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

                    // Add this new bulk action
                    Tables\Actions\BulkAction::make('generate_admission_numbers')
                        ->icon('heroicon-o-identification')
                        ->color('gray')
                        ->label('Generate Admission Numbers')
                        ->requiresConfirmation()
                        ->modalDescription('This will generate admission numbers for all selected applicants that don\'t have one.')
                        ->action(function (Collection $records) {
                            $generator = new \App\Services\AdmissionNumberGenerator();
                            $count = 0;

                            $records->each(function ($record) use ($generator, &$count) {
                                if (empty($record->admission_number)) {
                                    $record->update(['admission_number' => $generator->generate()]);
                                    $count++;
                                }
                            });

                            Notification::make()
                                ->success()
                                ->title('Admission Numbers Generated')
                                ->body("Generated {$count} admission number(s)")
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
            'view' => Pages\ViewAdmission::route('/{record}'),
            'edit' => Pages\EditAdmission::route('/{record}/edit'),
            'newstudent' => Pages\NewStudent::route('{record}/new-student'),
            'view-letter' => Pages\ViewAdmissionLetter::route('/{record}/letter'),

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
