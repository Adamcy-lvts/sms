<?php

namespace App\Filament\Sms\Resources;

use Closure;
use App\Models\Lga;
use Filament\Forms;
use App\Models\Term;
use Filament\Tables;
use App\Models\State;
use App\Models\Status;
use App\Models\Payment;
use App\Models\Student;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Helpers\Options;
use Filament\Forms\Form;
use App\Models\ClassRoom;
use Filament\Tables\Table;
use App\Models\PaymentPlan;
use App\Models\PaymentType;
use Filament\Support\RawJs;
use App\Models\PaymentMethod;
use Filament\Facades\Filament;
use App\Models\AcademicSession;
use App\Models\StudentMovement;
use Illuminate\Validation\Rule;
use Filament\Resources\Resource;
use App\Models\StudentPaymentPlan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Radio;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Illuminate\Support\Facades\Blade;
use App\Services\StudentStatusService;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\StudentResource\Pages;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use App\Filament\Sms\Resources\StudentResource\RelationManagers;

class StudentResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'School Management';

    public static function getPermissionPrefixes(): array
    {
        // These permissions will only be generated for the Student resource
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'promote', 
            'change_status',
            'record_payment',
            'bulk_promote',
            'bulk_status_change',
            'bulk_payment',
            'import',
            'profile', // Changed from 'student_profile' to just 'profile'
        ];
    }

    public static function form(Form $form): Form
    {
        $school = Filament::getTenant();

        if ($school->hasFeature('Admission Management')) {
            return $form->schema(self::getSimpleStudentForm());
        } else {
            return $form
                ->schema([
                    Wizard::make([
                        Wizard\Step::make('Personal Information')
                            ->columns(2)
                            ->schema([
                                Fieldset::make('Personal Information')
                                    ->schema([

                                        Forms\Components\Select::make('academic_session_id')
                                            ->label('Academic Session')
                                            ->options(AcademicSession::pluck('name', 'id'))
                                            ->default(function () {
                                                return config('app.current_session')->id ?? null;
                                            }),
                                        Forms\Components\TextInput::make('first_name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('last_name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('middle_name')
                                            ->maxLength(255),
                                        Forms\Components\DatePicker::make('date_of_birth')->native(false)
                                            ->required()
                                            ->maxDate(now()->subYears(3)),

                                        Forms\Components\Select::make('gender')->options(Options::gender())
                                            ->required(),
                                        Forms\Components\TextInput::make('phone_number')
                                            ->tel()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->maxLength(255),
                                        Forms\Components\Select::make('state_id')->options(State::all()->pluck('name', 'id')->toArray())->live()->label('State of Origin')
                                            ->required(),
                                        Forms\Components\Select::make('lga_id')
                                            ->options(fn(Forms\Get $get): Collection => Lga::query()
                                                ->where('state_id', $get('state_id'))
                                                ->pluck('name', 'id'))->required()->label('Local Government Area'),
                                    ]),
                                // Forms\Components\FileUpload::make('profile_picture')->label('Profile Picture')->disk('public')->directory('student_profile')->columnSpan(2),
                                Forms\Components\FileUpload::make('profile_picture')->label('Profile Picture')->disk('public')->directory("{$school->slug}/student_profile_pictures")->columnSpan(2)

                            ]),
                        Wizard\Step::make('Personal Information 2')
                            ->schema([
                                Fieldset::make('Personal Information 2')
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
                                        Section::make()
                                            ->schema(fn(Forms\Get $get): array => match ($get('type')) {
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
                                        Forms\Components\TextInput::make('admission_number')
                                            ->maxLength(255),
                                        Forms\Components\Select::make('status_id')->options(Status::where('type', 'student')->pluck('name', 'id')->toArray())->label('Status')
                                            ->required(),
                                        Forms\Components\Select::make('class_room_id')->label('Class Room')->options(ClassRoom::all()->pluck('name', 'id'))->required(),
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
                                Fieldset::make('Emergency Contact Information')
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
    }

    protected static function getSimpleStudentForm(): array
    {
        $school = Filament::getTenant();
        return [
            Fieldset::make('Student Information')
                ->schema([
                    TextInput::make('school_id')->hidden(),
                    FileUpload::make('profile_picture')->label('Profile Picture')->disk('public')->directory("{$school->slug}/student_profile_pictures")->columnSpan(2),
                    TextInput::make('first_name')->label('First Name')->required(),
                    TextInput::make('last_name')->label('Last Name')->required(),
                    TextInput::make('middle_name')->label('Middle Name'),
                    DatePicker::make('date_of_birth')->label('Date of Birth')->required(),
                    TextInput::make('phone_number')->label('Phone'),
                    Select::make('status_id')->label('Status')->options(Status::where('type', 'student')->pluck('name', 'id'))->default(1)->required(),
                    TextInput::make('identification_number')->label('Identification Number'),
                ])
                ->columns(2),

            Fieldset::make('Give Student Class Room')
                ->schema([
                    Select::make('class_room_id')->label('Class Room')->options(ClassRoom::all()->pluck('name', 'id'))->required(),
                ])
                ->columns(2)
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('S/N')
                    ->rowIndex(),

                ImageColumn::make('profile_picture')
                    ->label('Profile Picture')
                    ->circular()
                    ->defaultImageUrl(url('/img/default.jpg'))
                    ->height(50),

                Tables\Columns\TextColumn::make('admission.admission_number')
                    ->label('Admission No.')
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Student Name')
                    ->searchable(['first_name', 'last_name', 'middle_name'])
                    ->sortable(),
                // ->description(fn(Student $record): string => $record->admission?->admission_number ?? ''),

                Tables\Columns\TextColumn::make('classRoom.name')
                    ->label('Class')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('admission.gender')
                    ->label('Gender')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Male' => 'blue',
                        'Female' => 'pink',
                        default => 'gray',
                    })
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'primary',
                        'inactive' => 'gray',
                        'graduated' => 'success',
                        'suspended' => 'warning',
                        'expelled' => 'danger',
                        'transferred' => 'warning',
                        'deceased' => 'gray',
                        'promoted' => 'success',
                        default => 'gray',
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('admission.guardian_name')
                    ->label('Guardian')
                    ->searchable()
                    ->toggleable()
                    ->description(fn(Student $record): string => $record->admission?->guardian_phone_number ?? ''),

                Tables\Columns\TextColumn::make('admission.date_of_birth')
                    ->label('Date of Birth')
                    ->date('d M Y')
                    ->description(function (Student $record): string {
                        if (!$record->admission?->date_of_birth) return 'N/A';

                        $birthDate = \Carbon\Carbon::parse($record->admission->date_of_birth);
                        $age = max(0, $birthDate->age);

                        return $age . ' years';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('admission.admitted_date')
                    ->label('Admission Date')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('School/Tuition Fees Status')
                    ->badge()
                    ->formatStateUsing(function (Model $record) {
                        $currentSession = config('app.current_session');
                        $currentTerm = config('app.current_term');

                        // Check for session payment first
                        $sessionPayment = Payment::where('student_id', $record->id)
                            ->where('academic_session_id', $currentSession?->id)
                            ->where('is_tuition', true)
                            ->where('payment_plan_type', 'session')
                            ->whereHas('status', fn($q) => $q->where('name', 'paid'))
                            ->first();

                        if ($sessionPayment) {
                            return 'Full Session Paid';
                        }

                        // Check for term payment
                        $termPayment = Payment::where('student_id', $record->id)
                            ->where('term_id', $currentTerm?->id)
                            ->where('is_tuition', true)
                            ->where('payment_plan_type', 'term')
                            ->whereHas('status', fn($q) => $q->where('name', 'paid'))
                            ->first();

                        if ($termPayment) {
                            return 'Current Term Paid';
                        }

                        // Check for partial payments
                        $partialPayment = Payment::where('student_id', $record->id)
                            ->where('academic_session_id', $currentSession?->id)
                            ->where('is_tuition', true)
                            ->whereHas('status', fn($q) => $q->where('name', 'partial'))
                            ->first();

                        if ($partialPayment) {
                            return 'Partial Payment';
                        }

                        return 'Not Paid';
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'Full Session Paid' => 'success',
                        'Current Term Paid' => 'info',
                        'Partial Payment' => 'warning',
                        'Not Paid' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        $currentSession = config('app.current_session');
                        $currentTerm = config('app.current_term');

                        return $query
                            ->leftJoin('payments', function ($join) use ($currentSession, $currentTerm) {
                                $join->on('students.id', '=', 'payments.student_id')
                                    ->where('payments.is_tuition', true)
                                    ->where(function ($q) use ($currentSession, $currentTerm) {
                                        $q->where(function ($sq) use ($currentSession) {
                                            $sq->where('academic_session_id', $currentSession?->id)
                                                ->where('payment_plan_type', 'session');
                                        })->orWhere(function ($sq) use ($currentTerm) {
                                            $sq->where('term_id', $currentTerm?->id)
                                                ->where('payment_plan_type', 'term');
                                        });
                                    });
                            })
                            ->orderBy('payments.payment_plan_type', $direction)
                            ->orderBy('payments.status_id', $direction)
                            ->select('students.*');
                    })
                    ->description(function (Model $record) {
                        $currentSession = config('app.current_session');

                        $payment = Payment::where('student_id', $record->id)
                            ->where('academic_session_id', $currentSession?->id)
                            ->where('is_tuition', true)
                            ->latest('paid_at')
                            ->first();

                        if (!$payment) return null;

                        return "Last paid: " . $payment->paid_at->format('j M, Y');
                    }),
            ])
            ->defaultSort('admission.admission_number', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('class_room_id')
                    ->label('Class')
                    ->options(fn() => ClassRoom::pluck('name', 'id')->toArray())
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('admission.gender')
                    ->label('Gender')
                    ->options([
                        'Male' => 'Male',
                        'Female' => 'Female',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('status_id')
                    ->label('Status')
                    ->options(fn() => Status::where('type', 'student')->pluck('name', 'id')->toArray())
                    ->multiple()
                    ->preload(),

                Tables\Filters\Filter::make('payment_status')
                    ->form([
                        Forms\Components\Select::make('payment_status')
                            ->label('Tuition Payment Status')
                            ->multiple()
                            ->options([
                                'no_payment' => 'No Payment Made',
                                'full_session_paid' => 'Full Session Paid',
                                'current_term_paid' => 'Current Term Paid',
                                'partial_payment' => 'Partial Payment',
                                'defaulters' => 'All Defaulters'
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['payment_status'] ?? null, function ($query) use ($data) {
                            $currentSession = config('app.current_session');
                            $currentTerm = config('app.current_term');

                            $query->where(function ($q) use ($data, $currentSession, $currentTerm) {
                                foreach ($data['payment_status'] as $status) {
                                    switch ($status) {
                                        case 'no_payment':
                                            $q->orWhereDoesntHave('payments', function ($query) use ($currentSession) {
                                                $query->where('is_tuition', true)
                                                    ->where('academic_session_id', $currentSession->id);
                                            });
                                            break;

                                        case 'full_session_paid':
                                            $q->orWhereHas('payments', function ($query) use ($currentSession) {
                                                $query->where('is_tuition', true)
                                                    ->where('academic_session_id', $currentSession->id)
                                                    ->where('payment_plan_type', 'session')
                                                    ->whereColumn('deposit', '>=', 'amount')
                                                    ->whereHas(
                                                        'status',
                                                        fn($sq) =>
                                                        $sq->where('name', 'paid')
                                                    );
                                            });
                                            break;

                                        case 'current_term_paid':
                                            $q->orWhereHas('payments', function ($query) use ($currentTerm) {
                                                $query->where('is_tuition', true)
                                                    ->where('term_id', $currentTerm->id)
                                                    ->where('payment_plan_type', 'term')
                                                    ->whereColumn('deposit', '>=', 'amount')
                                                    ->whereHas(
                                                        'status',
                                                        fn($sq) =>
                                                        $sq->where('name', 'paid')
                                                    );
                                            });
                                            break;

                                        case 'partial_payment':
                                            $q->orWhereHas('payments', function ($query) use ($currentSession) {
                                                $query->where('is_tuition', true)
                                                    ->where('academic_session_id', $currentSession->id)
                                                    ->whereColumn('deposit', '<', 'amount')
                                                    ->whereHas(
                                                        'status',
                                                        fn($sq) =>
                                                        $sq->where('name', 'partial')
                                                    );
                                            });
                                            break;

                                        case 'defaulters':
                                            $q->orWhere(function ($query) use ($currentSession) {
                                                $query->whereDoesntHave('payments', function ($sq) use ($currentSession) {
                                                    $sq->where('is_tuition', true)
                                                        ->where('academic_session_id', $currentSession->id);
                                                })->orWhereHas('payments', function ($sq) use ($currentSession) {
                                                    $sq->where('is_tuition', true)
                                                        ->where('academic_session_id', $currentSession->id)
                                                        ->where(function ($subsq) {
                                                            $subsq->whereColumn('deposit', '<', 'amount')
                                                                ->orWhereHas(
                                                                    'status',
                                                                    fn($status) =>
                                                                    $status->whereIn('name', ['pending', 'partial'])
                                                                );
                                                        });
                                                });
                                            });
                                            break;
                                    }
                                }
                            });
                        });
                    }),

                Tables\Filters\Filter::make('admitted_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Admitted From'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Admitted Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereHas('admission', function ($query) use ($date) {
                                    $query->whereDate('admitted_date', '>=', $date);
                                })
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereHas('admission', function ($query) use ($date) {
                                    $query->whereDate('admitted_date', '<=', $date);
                                })
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(), 
                    Tables\Actions\EditAction::make(),
                      
                    Tables\Actions\Action::make('payTuition')
                        ->visible(fn(): bool => auth()->user()->can('record_payment_student'))
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->label('Pay Tuition')
                        ->modalHeading(fn(Student $record) => "Record Tuition Payment for {$record->full_name}")
                        ->modalDescription(fn(Student $record) => "Class: {$record->classRoom?->name}")
                        ->form(function (Student $record) {
                            $session = config('app.current_session');
                            $term = config('app.current_term');
                            $classLevel = $record->classRoom?->level;

                            // Get tuition payment type for this class level
                            $tuitionType = PaymentType::query()
                                ->where('school_id', Filament::getTenant()->id)
                                ->where('is_tuition', true)
                                ->whereHas('paymentPlans', function ($query) use ($classLevel) {
                                    $query->where('class_level', $classLevel);
                                })
                                ->first();

                            // Calculate default amounts for session payment
                            $defaultPlanType = 'session';
                            $defaultAmount = $tuitionType?->getAmountForClass($classLevel, $defaultPlanType) ?? 0;

                            return [
                                Grid::make(['default' => 1, 'lg' => 3])
                                    ->schema([
                                        Section::make('Payment Information')
                                            ->columnSpan(1)
                                            ->schema([
                                                TextInput::make('student_name')
                                                    ->label('Student')
                                                    ->default($record->full_name)
                                                    ->disabled(),

                                                TextInput::make('class_name')
                                                    ->label('Class')
                                                    ->default($record->classRoom?->name)
                                                    ->disabled(),

                                                Select::make('academic_session_id')
                                                    ->label('Academic Session')
                                                    ->options(AcademicSession::pluck('name', 'id'))
                                                    ->default($session?->id),

                                                Select::make('term_id')
                                                    ->label('Term')
                                                    ->options(Term::pluck('name', 'id'))
                                                    ->default($term?->id),

                                                Select::make('payment_method_id')
                                                    ->label('Payment Method')
                                                    ->options(PaymentMethod::where('active', true)->pluck('name', 'id'))
                                                    ->required(),

                                                TextInput::make('reference')
                                                    ->default('PAY-' . strtoupper(uniqid()))
                                                    ->disabled()
                                                    ->dehydrated(),

                                                Toggle::make('enable_partial_payment')
                                                    ->label('Enable Installment Payment')
                                                    ->default(false)
                                                    ->visible(fn() => $tuitionType?->installment_allowed)
                                                    ->live()
                                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                        if (!$state) {
                                                            $amount = floatval($get('amount'));
                                                            $set('deposit', $amount);
                                                            $set('balance', 0);
                                                        }
                                                    }),
                                            ]),

                                        Section::make('Tuition Details')
                                            ->columnSpan(2)
                                            ->schema([
                                                Radio::make('payment_plan_type')
                                                    ->label('Payment Plan')
                                                    ->options([
                                                        'session' => 'Full Session Payment (' .
                                                            formatNaira($tuitionType?->getAmountForClass($classLevel, 'session')) . ')',
                                                        'term' => 'Term Payment (' .
                                                            formatNaira($tuitionType?->getAmountForClass($classLevel, 'term')) . ')'
                                                    ])
                                                    ->default($defaultPlanType)
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, Set $set) use ($tuitionType, $classLevel) {
                                                        $amount = $tuitionType?->getAmountForClass($classLevel, $state);
                                                        $set('amount', $amount);
                                                        $set('deposit', $amount);
                                                    }),

                                                TextInput::make('amount')
                                                    ->label('Amount')
                                                    ->disabled()
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters(['₦', ','])
                                                    ->dehydrated()
                                                    ->prefix('₦')
                                                    ->default($defaultAmount),

                                                TextInput::make('deposit')
                                                    ->label('Amount to Pay')
                                                    ->numeric()
                                                    ->prefix('₦')
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters(['₦', ','])
                                                    ->required()
                                                    ->default(fn() => $defaultAmount)
                                                    ->disabled(fn(Get $get) => !$get('enable_partial_payment'))
                                                    ->live(debounce: 800)
                                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                        if ($state === null) return;
                                                        $amount = str_replace([',', '₦'], '', $get('amount'));
                                                        $state = str_replace([',', '₦'], '', $state);
                                                        $balance = floatval($amount) - floatval($state);
                                                        $deposit = floatval($state);
                                                        $amount = floatval($amount);

                                                        // Prevent deposit from exceeding amount
                                                        if ($deposit > $amount) {
                                                            $set('deposit', $amount);
                                                            $set('balance', 0);
                                                            $deposit = $amount;
                                                            Notification::make()
                                                                ->warning()
                                                                ->title('Invalid Amount')
                                                                ->body("Amount to pay cannot exceed total amount of ₦" . number_format($amount, 2))
                                                                ->send();
                                                        }
                                                        $set('balance', $balance);
                                                    }),

                                                TextInput::make('balance')
                                                    ->label('Balance')
                                                    ->disabled()
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters(['₦', ','])
                                                    ->dehydrated()
                                                    ->prefix('₦')
                                                    ->default(0),

                                                TextInput::make('payer_name')
                                                    ->label('Payer Name')
                                                    ->default($record->admission?->guardian_name)
                                                    ->required(),

                                                TextInput::make('payer_phone_number')
                                                    ->label('Payer Phone')
                                                    ->default($record->admission?->guardian_phone_number)
                                                    ->tel(),

                                                DateTimePicker::make('paid_at')
                                                    ->label('Payment Date')
                                                    ->default(now())
                                                    ->required(),

                                                Hidden::make('payment_category')->default('tuition'),
                                                Hidden::make('payment_type_id')->default($tuitionType?->id),
                                            ]),
                                    ]),
                            ];
                        })
                        ->action(function (Student $record, array $data) {
                            $tenant = Filament::getTenant();
                            $payment = null;

                            // Check for existing payments
                            $existingPayments = Payment::query()
                                ->where('school_id', $tenant->id)
                                ->where('student_id', $record->id)
                                ->where('academic_session_id', $data['academic_session_id'])
                                ->where('term_id', $data['term_id'])
                                ->where('is_tuition', true)
                                ->whereHas('status', function ($query) {
                                    $query->where('name', 'paid');
                                })
                                ->with(['paymentItems.paymentType'])
                                ->get();

                            // If we found any existing payments
                            if ($existingPayments->isNotEmpty()) {
                                // Format payment details
                                $paymentDetails = $existingPayments->map(function ($payment) {
                                    return sprintf(
                                        "Reference: %s\nPaid On: %s\nAmount: ₦%s",
                                        $payment->reference,
                                        $payment->paid_at->format('j M, Y'),
                                        number_format($payment->amount, 2)
                                    );
                                })->join("\n\n");

                                // Build the complete message
                                $message = sprintf(
                                    "%s (%s) has already made tuition payment for %s - %s:\n\n%s",
                                    $record->full_name,           // Student name
                                    $record->classRoom->name,     // Class name
                                    AcademicSession::find($data['academic_session_id'])->name, // Session name
                                    Term::find($data['term_id'])->name, // Term name
                                    $paymentDetails               // Payment details
                                );

                                // Show the error notification
                                Notification::make()
                                    ->danger()
                                    ->title('Duplicate Payment Detected')
                                    ->body($message)
                                    ->actions([
                                        Action::make('view_payment_history')
                                            ->label('View Payment History')
                                            ->url(PaymentResource::getUrl('index', [
                                                'tenant' => $tenant,
                                                'tableFilters' => [
                                                    'student_id' => [$record->id],
                                                    'academic_session_id' => [$data['academic_session_id']],
                                                    'term_id' => [$data['term_id']],
                                                ]
                                            ]))
                                            ->button(),
                                    ])
                                    ->persistent()
                                    ->send();

                                return;
                            }

                            // Continue with existing validation and payment processing
                            // ...existing code for payment processing...

                            $tuitionType = PaymentType::find($data['payment_type_id']);
                            if (
                                $data['enable_partial_payment'] &&
                                $tuitionType?->installment_allowed &&
                                floatval($data['deposit']) < floatval($tuitionType->min_installment_amount)
                            ) {
                                Notification::make()
                                    ->danger()
                                    ->title('Invalid Amount')
                                    ->body("Minimum installment amount is ₦" . number_format($tuitionType->min_installment_amount, 2))
                                    ->send();
                                return;
                            }
                            DB::transaction(function () use ($record, $data, $tenant, &$payment) {
                                // Get the tuition payment type
                                $tuitionType = PaymentType::find($data['payment_type_id']);
                                $classLevel = $record->classRoom->getLevel();

                                // Create or update student payment plan
                                $paymentPlan = PaymentPlan::where([
                                    'payment_type_id' => $tuitionType->id,
                                    'class_level' => $classLevel,
                                ])->first();

                                if ($paymentPlan) {
                                    StudentPaymentPlan::updateOrCreate(
                                        [
                                            'student_id' => $record->id,
                                            'academic_session_id' => $data['academic_session_id'],
                                        ],
                                        [
                                            'school_id' => $tenant->id,
                                            'payment_plan_id' => $paymentPlan->id,
                                            'created_by' => auth()->id(),
                                            'notes' => "Plan selected during tuition payment {$data['reference']}"
                                        ]
                                    );
                                }

                                // Create payment with tuition-specific data
                                $payment = Payment::create([
                                    'school_id' => $tenant->id,
                                    'student_id' => $record->id,
                                    'class_room_id' => $record->class_room_id,
                                    'receiver_id' => auth()->id(),
                                    'payment_method_id' => $data['payment_method_id'],
                                    'academic_session_id' => $data['academic_session_id'],
                                    'term_id' => $data['term_id'],
                                    'status_id' => Status::where('type', 'payment')
                                        ->where('name', $data['balance'] > 0 ? 'partial' : 'paid')
                                        ->first()?->id,
                                    'reference' => $data['reference'],
                                    'payer_name' => $data['payer_name'],
                                    'payer_phone_number' => $data['payer_phone_number'],
                                    'amount' => $data['amount'],
                                    'deposit' => $data['deposit'],
                                    'balance' => $data['balance'],
                                    'is_tuition' => true,
                                    'payment_plan_type' => $data['payment_plan_type'],
                                    'payment_category' => 'tuition',
                                    'paid_at' => $data['paid_at'],
                                    'created_by' => auth()->id(),
                                    'updated_by' => auth()->id(),
                                ]);

                                // Create single payment item for tuition
                                $payment->paymentItems()->create([
                                    'payment_type_id' => $data['payment_type_id'],
                                    'amount' => $data['amount'],
                                    'deposit' => $data['deposit'],
                                    'balance' => $data['balance'],
                                    'is_tuition' => true,
                                ]);
                            });

                            if ($payment) {
                                Notification::make()
                                    ->success()
                                    ->title('Tuition Payment Recorded')
                                    ->body("Payment of ₦" . number_format($payment->deposit, 2) . " has been recorded.")
                                    ->actions([
                                        Action::make('view_receipt')
                                            ->label('View Receipt')
                                            ->url(fn() => PaymentResource::getUrl('view', ['record' => $payment->id]))
                                            ->button()
                                            ->openUrlInNewTab(),
                                    ])
                                    ->persistent()
                                    ->send();
                            }
                        })
                        ->modalWidth('4xl'),

                    // Promote Student Action
                    Tables\Actions\Action::make('promote')
                        ->visible(fn(): bool => auth()->user()->can('promote_student'))
                        ->icon('heroicon-o-arrow-up-circle')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('class_room_id')
                                ->label('New Class')
                                ->options(ClassRoom::pluck('name', 'id'))
                                ->required(),
                            Forms\Components\Textarea::make('note')
                                ->label('Promotion Note')
                                ->maxLength(255),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Promote Student')
                        ->modalDescription(fn(Student $record) => "Are you sure you want to promote {$record->admission->full_name}?")
                        ->action(function (Student $record, array $data): void {
                            DB::transaction(function () use ($record, $data) {
                                $oldClassId = $record->class_room_id;
                                $currentSession = config('app.current_session');
                                $nextSession = AcademicSession::where('start_date', '>', $currentSession->end_date)
                                    ->orderBy('start_date')
                                    ->first();

                                $record->update([
                                    'class_room_id' => $data['class_room_id'],
                                ]);

                                StudentMovement::create([
                                    'school_id' => Filament::getTenant()->id,
                                    'student_id' => $record->id,
                                    'from_class_id' => $oldClassId,
                                    'to_class_id' => $data['class_room_id'],
                                    'from_session_id' => $currentSession->id,
                                    'to_session_id' => $nextSession->id ?? $currentSession->id,
                                    'movement_type' => 'promotion',
                                    'movement_date' => now(),
                                    'reason' => $data['note'] ?? 'Individual promotion to new class'
                                ]);

                                Notification::make()
                                    ->success()
                                    ->title('Student Promoted')
                                    ->body('The student has been promoted successfully.')
                                    ->send();
                            });
                        }),

                    // Change Status Action
                    Tables\Actions\Action::make('changeStatus')
                        ->visible(fn(): bool => auth()->user()->can('change_status_student'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('status_id')
                                ->label('New Status')
                                ->options(fn() => Status::where('type', 'student')
                                    ->whereNotIn('name', ['Promoted']) // Exclude 'Promoted' status
                                    ->pluck('name', 'id'))
                                ->required(),
                            Forms\Components\Textarea::make('reason')
                                ->label('Reason for Status Change')
                                ->maxLength(255),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Change Student Status')
                        ->modalDescription(fn(Student $record) => "Change status for {$record->admission->full_name}")
                        ->action(function (Student $record, array $data): void {
                            DB::transaction(function () use ($record, $data) {
                                $statusService = new StudentStatusService();

                                try {
                                    $statusService->changeStatus(
                                        student: $record,
                                        newStatusId: $data['status_id'],
                                        reason: $data['reason'] ?? 'No reason provided'
                                    );

                                    Notification::make()
                                        ->success()
                                        ->title('Status Updated')
                                        ->body('The student status has been updated successfully.')
                                        ->send();
                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Error')
                                        ->body('Failed to update student status: ' . $e->getMessage())
                                        ->send();
                                }
                            });
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn(): bool => auth()->user()->can('delete_student')),

                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn(): bool => auth()->user()->can('delete_any_student')),

                    Tables\Actions\BulkAction::make('bulkTuitionPayment')
                        ->visible(fn(): bool => auth()->user()->can('bulk_payment_student'))
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->label('Pay Tuition')
                        ->form(function (Collection $records) {
                            $session = config('app.current_session');
                            $term = config('app.current_term');

                            // Get tuition types and amounts for each class level
                            $tuitionAmounts = collect();
                            foreach ($records as $student) {
                                $classLevel = $student->classRoom?->level;
                                $tuitionType = PaymentType::query()
                                    ->where('school_id', Filament::getTenant()->id)
                                    ->where('is_tuition', true)
                                    ->whereHas('paymentPlans', function ($query) use ($classLevel) {
                                        $query->where('class_level', $classLevel);
                                    })
                                    ->first();

                                $tuitionAmounts->push([
                                    'student' => $student,
                                    'amount_session' => $tuitionType?->getAmountForClass($classLevel, 'session') ?? 0,
                                    'amount_term' => $tuitionType?->getAmountForClass($classLevel, 'term') ?? 0,
                                ]);
                            }

                            $totalSessionAmount = $tuitionAmounts->sum('amount_session');
                            $totalTermAmount = $tuitionAmounts->sum('amount_term');

                            return [
                                Section::make('Selected Students')
                                    ->description('The following students will be processed for payment')
                                    ->schema([
                                        ViewField::make('students')
                                            ->view('filament.forms.components.students-table', [
                                                'students' => $tuitionAmounts,
                                                'totalSessionAmount' => $totalSessionAmount,
                                                'totalTermAmount' => $totalTermAmount,
                                            ])
                                    ])
                                    ->columnSpanFull(),
                                Section::make('Payment Details')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('academic_session_id')
                                            ->label('Academic Session')
                                            ->options(AcademicSession::pluck('name', 'id'))
                                            ->default($session?->id)
                                            ->required(),

                                        Select::make('term_id')
                                            ->label('Term')
                                            ->options(Term::pluck('name', 'id'))
                                            ->default($term?->id)
                                            ->required(),

                                        Select::make('payment_method_id')
                                            ->label('Payment Method')
                                            ->options(PaymentMethod::where('active', true)->pluck('name', 'id'))
                                            ->required(),

                                        Radio::make('payment_plan_type')
                                            ->label('Payment Plan')
                                            ->options([
                                                'session' => 'Full Session Payment',
                                                'term' => 'Term Payment'
                                            ])
                                            ->default('session')
                                            ->required()
                                            ->live(),

                                        Toggle::make('enable_partial_payment')
                                            ->label('Enable Installment Payment')
                                            ->default(false)
                                            ->live(),

                                        TextInput::make('payer_name')
                                            ->label('Payer Name'),

                                        TextInput::make('payer_phone_number')
                                            ->label('Payer Phone')
                                            ->tel(),

                                        DateTimePicker::make('paid_at')
                                            ->label('Payment Date')
                                            ->default(now())
                                            ->required(),
                                    ]),
                            ];
                        })
                        ->action(function (Collection $records, array $data) {
                            $tenant = Filament::getTenant();
                            $successCount = 0;
                            $failedStudents = [];

                            // Check for duplicate payments before starting the transaction
                            $duplicatePayments = [];
                            foreach ($records as $student) {
                                // Check for existing payments
                                $existingPayments = Payment::query()
                                    ->where('school_id', $tenant->id)
                                    ->where('student_id', $student->id)
                                    ->where('academic_session_id', $data['academic_session_id'])
                                    ->where('term_id', $data['term_id'])
                                    ->where('is_tuition', true)
                                    ->whereHas('status', function ($query) {
                                        $query->where('name', 'paid');
                                    })
                                    ->with(['paymentItems.paymentType'])
                                    ->get();

                                if ($existingPayments->isNotEmpty()) {
                                    $duplicatePayments[] = [
                                        'student' => $student,
                                        'payments' => $existingPayments->map(function ($payment) {
                                            return [
                                                'reference' => $payment->reference,
                                                'paid_at' => $payment->paid_at->format('j M, Y'),
                                                'amount' => number_format($payment->amount, 2),
                                            ];
                                        })->toArray()
                                    ];
                                }
                            }

                            // If there are duplicate payments, show notification and stop
                            if (!empty($duplicatePayments)) {
                                $message = "The following students already have payments for this period:\n\n";
                                foreach ($duplicatePayments as $duplicate) {
                                    $message .= "{$duplicate['student']->full_name} ({$duplicate['student']->classRoom->name}):\n";
                                    foreach ($duplicate['payments'] as $payment) {
                                        $message .= "- Ref: {$payment['reference']}, Paid: {$payment['paid_at']}, Amount: ₦{$payment['amount']}\n";
                                    }
                                    $message .= "\n";
                                }

                                Notification::make()
                                    ->danger()
                                    ->title('Duplicate Payments Detected')
                                    ->body($message)
                                    ->actions([
                                        Action::make('view_payments')
                                            ->label('View Payment History')
                                            ->url(PaymentResource::getUrl('index', [
                                                'tenant' => $tenant,
                                                'tableFilters' => [
                                                    'student_id' => $duplicatePayments[0]['student']->id,
                                                    'academic_session_id' => [$data['academic_session_id']],
                                                    'term_id' => [$data['term_id']],
                                                ]
                                            ]))
                                            ->button(),
                                    ])
                                    ->persistent()
                                    ->send();

                                return;
                            }

                            // Continue with existing bulk payment processing
                            // ...existing code for bulk payment processing...

                            DB::transaction(function () use ($records, $data, $tenant, &$successCount, &$failedStudents) {
                                foreach ($records as $student) {
                                    try {
                                        $classLevel = $student->classRoom?->level;

                                        // Get tuition payment type for this class level
                                        $tuitionType = PaymentType::query()
                                            ->where('school_id', $tenant->id)
                                            ->where('is_tuition', true)
                                            ->whereHas('paymentPlans', function ($query) use ($classLevel) {
                                                $query->where('class_level', $classLevel);
                                            })
                                            ->first();

                                        if (!$tuitionType) {
                                            throw new \Exception("No tuition type found for {$student->full_name}'s class level");
                                        }

                                        // Calculate amount based on payment plan
                                        $amount = $tuitionType->getAmountForClass($classLevel, $data['payment_plan_type']);

                                        // Set deposit based on partial payment setting
                                        $deposit = $amount;
                                        if ($data['enable_partial_payment'] && $tuitionType->installment_allowed) {
                                            $deposit = $tuitionType->min_installment_amount;
                                        }

                                        // Create payment plan if needed
                                        $paymentPlan = PaymentPlan::where([
                                            'payment_type_id' => $tuitionType->id,
                                            'class_level' => $classLevel,
                                        ])->first();

                                        if ($paymentPlan) {
                                            StudentPaymentPlan::updateOrCreate(
                                                [
                                                    'student_id' => $student->id,
                                                    'academic_session_id' => $data['academic_session_id'],
                                                ],
                                                [
                                                    'school_id' => $tenant->id,
                                                    'payment_plan_id' => $paymentPlan->id,
                                                    'created_by' => auth()->id(),
                                                    'notes' => "Plan selected during bulk tuition payment"
                                                ]
                                            );
                                        }

                                        // Create payment
                                        $payment = Payment::create([
                                            'school_id' => $tenant->id,
                                            'student_id' => $student->id,
                                            'class_room_id' => $student->class_room_id,
                                            'receiver_id' => auth()->id(),
                                            'payment_method_id' => $data['payment_method_id'],
                                            'academic_session_id' => $data['academic_session_id'],
                                            'term_id' => $data['term_id'],
                                            'status_id' => Status::where('type', 'payment')
                                                ->where('name', $deposit < $amount ? 'partial' : 'paid')
                                                ->first()?->id,
                                            'reference' => 'PAY-' . strtoupper(uniqid()),
                                            'payer_name' => $data['payer_name'],
                                            'payer_phone_number' => $data['payer_phone_number'],
                                            'amount' => $amount,
                                            'deposit' => $deposit,
                                            'balance' => $amount - $deposit,
                                            'is_tuition' => true,
                                            'payment_plan_type' => $data['payment_plan_type'],
                                            'payment_category' => 'tuition',
                                            'paid_at' => $data['paid_at'],
                                            'created_by' => auth()->id(),
                                            'updated_by' => auth()->id(),
                                        ]);

                                        // Create payment item
                                        $payment->paymentItems()->create([
                                            'payment_type_id' => $tuitionType->id,
                                            'amount' => $amount,
                                            'deposit' => $deposit,
                                            'balance' => $amount - $deposit,
                                            'is_tuition' => true,
                                        ]);

                                        $successCount++;
                                    } catch (\Exception $e) {
                                        $failedStudents[] = [
                                            'name' => $student->full_name,
                                            'error' => $e->getMessage()
                                        ];
                                    }
                                }
                            });

                            // Show appropriate notification
                            if (empty($failedStudents)) {
                                Notification::make()
                                    ->success()
                                    ->title('Bulk Tuition Payment Successful')
                                    ->body("Successfully processed payments for {$successCount} students.")
                                    ->persistent()
                                    ->send();
                            } else {
                                $failureMessage = "Processed {$successCount} payments successfully.\n\nFailed students:\n";
                                foreach ($failedStudents as $failure) {
                                    $failureMessage .= "- {$failure['name']}: {$failure['error']}\n";
                                }

                                Notification::make()
                                    ->warning()
                                    ->title('Partial Success')
                                    ->body($failureMessage)
                                    ->persistent()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalWidth('4xl')
                        ->modalHeading('Bulk Tuition Payment')
                        ->modalDescription('Process tuition payment for multiple students at once.')
                        ->deselectRecordsAfterCompletion(),

                    // Bulk Promote Action
                    Tables\Actions\BulkAction::make('bulkPromote')
                        ->visible(fn(): bool => auth()->user()->can('bulk_promote_student'))
                        ->icon('heroicon-o-arrow-up-circle')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('class_room_id')
                                ->label('New Class')
                                ->options(ClassRoom::pluck('name', 'id'))
                                ->required(),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Promote Selected Students')
                        ->action(function (Collection $records, array $data): void {
                            DB::transaction(function () use ($records, $data) {
                                $currentSession = config('app.current_session');
                                $nextSession = AcademicSession::where('start_date', '>', $currentSession->end_date)
                                    ->orderBy('start_date')
                                    ->first();

                                $records->each(function ($record) use ($data, $currentSession, $nextSession) {
                                    $oldClassId = $record->class_room_id;

                                    $record->update([
                                        'class_room_id' => $data['class_room_id'],
                                    ]);

                                    StudentMovement::create([
                                        'school_id' => Filament::getTenant()->id,
                                        'student_id' => $record->id,
                                        'from_class_id' => $oldClassId,
                                        'to_class_id' => $data['class_room_id'],
                                        'from_session_id' => $currentSession->id,
                                        'to_session_id' => $nextSession->id ?? $currentSession->id,
                                        'movement_type' => 'promotion',
                                        'movement_date' => now(),
                                        'reason' => 'Bulk promotion to new class',
                                        'status' => 'completed',  // Add this
                                        'processed_by' => auth()->id() // Add this
                                    ]);
                                });

                                Notification::make()
                                    ->success()
                                    ->title('Students Promoted')
                                    ->body('Selected students have been promoted successfully.')
                                    ->send();
                            });
                        }),

                    // Bulk Status Change
                    Tables\Actions\BulkAction::make('bulkStatusChange')
                        ->visible(fn(): bool => auth()->user()->can('bulk_status_change_student'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('status_id')
                                ->label('New Status')
                                ->options(fn() => Status::where('type', 'student')
                                    ->whereNotIn('name', ['Promoted'])
                                    ->pluck('name', 'id'))
                                ->required(),
                            Forms\Components\Textarea::make('reason')
                                ->label('Reason for Status Change')
                                ->maxLength(255)
                                ->required(),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Change Status for Selected Students')
                        ->action(function (Collection $records, array $data): void {
                            DB::transaction(function () use ($records, $data) {
                                $statusService = new StudentStatusService();
                                $failedStudents = [];

                                foreach ($records as $record) {
                                    try {
                                        $statusService->changeStatus(
                                            student: $record,
                                            newStatusId: $data['status_id'],
                                            reason: $data['reason'] ?? 'No reason provided'
                                        );
                                    } catch (\Exception $e) {
                                        $failedStudents[] = [
                                            'name' => $record->full_name,
                                            'error' => $e->getMessage()
                                        ];
                                    }
                                }

                                if (empty($failedStudents)) {
                                    Notification::make()
                                        ->success()
                                        ->title('Status Updated')
                                        ->body('All selected students statuses have been updated successfully.')
                                        ->send();
                                } else {
                                    $failureMessage = "Some students could not be updated:\n";
                                    foreach ($failedStudents as $failure) {
                                        $failureMessage .= "- {$failure['name']}: {$failure['error']}\n";
                                    }

                                    Notification::make()
                                        ->warning()
                                        ->title('Partial Update')
                                        ->body($failureMessage)
                                        ->persistent()
                                        ->send();
                                }
                            });
                        }),
                ]),
            ])
            ->emptyStateHeading('No students found')
            ->emptyStateDescription('Start by adding a new student record.')
            ->emptyStateIcon('heroicon-o-users')
            ->striped()
            ->defaultPaginationPageOption(25)
            ->persistFiltersInSession()
            ->persistSortInSession();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'first_name',
            'last_name',
            'middle_name',
            'admission.email',
            'phone_number',
            'admission_number',

        ];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'full_name' => $record->full_name,
            'phone' => $record->phone,
            'Status' => $record->status?->name,
            'class' => $record->classRoom?->name,
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return StudentResource::getUrl('view', ['record' => $record]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
            'view' => Pages\StudentProfile::route('/{record}/profile'),
        ];
    }
}
