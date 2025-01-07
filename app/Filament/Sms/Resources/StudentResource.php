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
use App\Models\PaymentType;
use App\Models\PaymentMethod;
use Filament\Facades\Filament;
use App\Models\AcademicSession;
use App\Models\StudentMovement;
use Illuminate\Validation\Rule;
use Filament\Resources\Resource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Wizard;
use Illuminate\Support\Facades\Blade;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Actions\Action;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\StudentResource\Pages;
use App\Filament\Sms\Resources\StudentResource\RelationManagers;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'School Management';

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
        return [
            Fieldset::make('Student Information')
                ->schema([
                    TextInput::make('school_id')->hidden(),
                    FileUpload::make('profile_picture')->label('Profile Picture')->required()->columnSpanFull(),
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
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
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

                    // Promote Student Action
                    Tables\Actions\Action::make('promote')
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
                                    'status_id' => Status::where('name', 'Promoted')->first()?->id,
                                ]);

                                StudentMovement::create([
                                    'student_id' => $record->id,
                                    'from_class_id' => $oldClassId,
                                    'to_class_id' => $data['class_room_id'],
                                    'from_session_id' => $currentSession->id,
                                    'to_session_id' => $nextSession->id,
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



                    Tables\Actions\Action::make('recordPayment')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->form([
                            Forms\Components\Section::make('Academic Information')
                                ->description('Select academic session and term')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Select::make('academic_session_id')
                                                ->label('Academic Session')
                                                ->options(fn() => AcademicSession::query()
                                                    ->where('school_id', Filament::getTenant()->id)
                                                    ->pluck('name', 'id'))
                                                ->default(fn() => config('app.current_session')->id)
                                                ->required(),

                                            Forms\Components\Select::make('term_id')
                                                ->label('Term')
                                                ->options(fn(Get $get) => Term::query()
                                                    ->where('academic_session_id', $get('academic_session_id'))
                                                    ->pluck('name', 'id'))
                                                ->default(fn() => config('app.current_term')->id)
                                                ->required(),
                                        ]),
                                ])->collapsible(),

                            Forms\Components\Section::make('Payment Details')
                                ->description('Select payment types and enter amount details')
                                ->schema([

                                    Forms\Components\Select::make('payment_method_id')
                                        ->label('Payment Method')
                                        ->options(fn() => PaymentMethod::where('active', true)->pluck('name', 'id'))
                                        ->required(),

                                    Forms\Components\Select::make('payment_type_ids')
                                        ->label('Payment Types')
                                        ->multiple()
                                        ->preload()
                                        ->options(fn() => PaymentType::where('school_id', Filament::getTenant()->id)
                                            ->where('active', true)
                                            ->pluck('name', 'id'))
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set, ?array $state) {
                                            $currentItems = collect($get('payment_items') ?? []);
                                            $selectedIds = collect($state ?? []);

                                            // Get all selected payment types with their amounts
                                            $paymentTypes = PaymentType::whereIn('id', $selectedIds)->get();

                                            // Add new items
                                            $newItems = $paymentTypes->map(function ($type) {
                                                return [
                                                    'payment_type_id' => $type->id,
                                                    'amount' => $type->amount,
                                                    'deposit' => $type->amount,
                                                    'balance' => 0
                                                ];
                                            })->toArray();

                                            // Set the payment items
                                            $set('payment_items', $newItems);

                                            // Calculate and set totals
                                            $totalAmount = $paymentTypes->sum('amount');
                                            $totalDeposit = $totalAmount; // Initially set to full payment
                                            $totalBalance = 0;

                                            $set('total_amount', $totalAmount);
                                            $set('total_deposit', $totalDeposit);
                                            $set('total_balance', $totalBalance);
                                        }),

                                    Forms\Components\Repeater::make('payment_items')
                                        ->schema([
                                            Forms\Components\Select::make('payment_type_id')
                                                ->label('Payment Type')
                                                ->options(fn() => PaymentType::where('school_id', Filament::getTenant()->id)
                                                    ->where('active', true)
                                                    ->pluck('name', 'id'))
                                                ->disabled()
                                                ->dehydrated()
                                                ->required(),

                                            Forms\Components\TextInput::make('amount')
                                                ->label('Amount')
                                                ->numeric()
                                                ->prefix('₦')
                                                ->required()
                                                ->disabled()
                                                ->dehydrated(),

                                            Forms\Components\TextInput::make('deposit')
                                                ->label('Amount to Pay')
                                                ->numeric()
                                                ->prefix('₦')
                                                ->required()
                                                ->live(debounce: 500)
                                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                    $amount = floatval($get('amount'));
                                                    $deposit = floatval($state ?? 0);
                                                    $enablePartial = $get('../../enable_partial_payment');

                                                    if (!$enablePartial) {
                                                        $deposit = $amount;
                                                        $set('deposit', $amount);
                                                    } elseif ($deposit > $amount) {
                                                        $deposit = $amount;
                                                        $set('deposit', $amount);
                                                    }

                                                    $balance = max(0, $amount - $deposit);
                                                    $set('balance', $balance);

                                                    // Calculate totals from all items
                                                    $items = collect($get('../../payment_items') ?? []);
                                                    $totalAmount = $items->sum('amount');
                                                    $totalDeposit = $items->sum('deposit');
                                                    $totalBalance = $items->sum('balance');

                                                    // Set the totals
                                                    $set('../../total_amount', $totalAmount);
                                                    $set('../../total_deposit', $totalDeposit);
                                                    $set('../../total_balance', $totalBalance);
                                                }),

                                            Forms\Components\TextInput::make('balance')
                                                ->label('Balance')
                                                ->numeric()
                                                ->prefix('₦')
                                                ->disabled()
                                                ->dehydrated(),
                                        ])
                                        ->defaultItems(0)
                                        ->addable(false)
                                        ->deletable(false)
                                        ->reorderable(false)
                                        ->columns(4)
                                        ->columnSpanFull()
                                        ->live(),

                                    Forms\Components\Toggle::make('enable_partial_payment')
                                        ->label('Enable Partial Payment')
                                        ->default(false)
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                            if (!$state) {
                                                // Reset to full payment when disabled
                                                $items = $get('payment_items');
                                                foreach ($items as $key => $item) {
                                                    $set("payment_items.{$key}.deposit", $item['amount']);
                                                    $set("payment_items.{$key}.balance", 0);
                                                }

                                                // Update totals
                                                $items = collect($items);
                                                $set('total_deposit', $items->sum('amount'));
                                                $set('total_balance', 0);
                                            }
                                        }),
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('total_amount')
                                                ->label('Total Amount')
                                                ->numeric()
                                                ->prefix('₦')
                                                ->disabled(),

                                            Forms\Components\TextInput::make('total_deposit')
                                                ->label('Total Amount to Pay')
                                                ->numeric()
                                                ->prefix('₦')
                                                ->disabled(),

                                            Forms\Components\TextInput::make('total_balance')
                                                ->label('Total Balance')
                                                ->numeric()
                                                ->prefix('₦')
                                                ->disabled(),
                                        ]),
                                ])->columns(1),

                            Forms\Components\Section::make('Payer Information')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('payer_name')
                                                ->label('Payer Name')
                                                ->default(fn(Student $record) => $record->admission?->guardian_name)
                                                ->required(),

                                            Forms\Components\TextInput::make('payer_phone_number')
                                                ->label('Payer Phone')
                                                ->default(fn(Student $record) => $record->admission?->guardian_phone_number)
                                                ->tel(),
                                        ]),

                                    Forms\Components\TextInput::make('reference')
                                        ->label('Payment Reference')
                                        ->default(fn() => 'PAY-' . strtoupper(uniqid()))
                                        ->readonly()
                                        ->dehydrated(),
                                ]),

                            Forms\Components\Section::make('Additional Information')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\DateTimePicker::make('due_date')
                                                ->label('Due Date')
                                                ->required()
                                                ->minDate(now())
                                                ->default(now()->addDays(7)),

                                            Forms\Components\DateTimePicker::make('paid_at')
                                                ->label('Paid At')
                                                ->default(now())
                                                ->required(),
                                        ]),

                                    Forms\Components\Textarea::make('remark')
                                        ->label('Remark')
                                        ->maxLength(255)
                                        ->columnSpan(2),

                                    Forms\Components\KeyValue::make('meta_data')
                                        ->label('Additional Details')
                                        ->columnSpan(2),
                                ]),
                        ])
                        ->modalHeading('Record Payment')
                        ->modalWidth('5xl')
                        ->action(function (Student $record, array $data): void {
                            $tenant = Filament::getTenant();

                            // Check for duplicate payments first
                            $existingPayments = Payment::query()
                                ->where('school_id', $tenant->id)
                                ->where('student_id', $record->id)
                                ->where('academic_session_id', $data['academic_session_id'])
                                ->where('term_id', $data['term_id'])
                                ->whereHas('status', function ($query) {
                                    $query->where('name', 'paid');
                                })
                                ->whereHas('paymentItems', function ($query) use ($data) {
                                    $query->whereIn('payment_type_id', $data['payment_type_ids'])
                                        ->where(function ($q) {
                                            $q->where('balance', 0)
                                                ->orWhere('deposit', DB::raw('amount'));
                                        });
                                })
                                ->with(['paymentItems.paymentType'])
                                ->get();

                            if ($existingPayments->isNotEmpty()) {
                                // Collect all duplicate payment items
                                $duplicateItems = collect();

                                foreach ($existingPayments as $payment) {
                                    $items = $payment->paymentItems
                                        ->whereIn('payment_type_id', $data['payment_type_ids'])
                                        ->map(function ($item) use ($payment) {
                                            return [
                                                'name' => $item->paymentType->name,
                                                'amount' => $item->amount,
                                                'reference' => $payment->reference,
                                                'paid_at' => $payment->paid_at,
                                            ];
                                        });
                                    $duplicateItems = $duplicateItems->concat($items);
                                }

                                // Group items by name
                                $groupedItems = $duplicateItems->groupBy('name')->map->first();

                                // Format items list
                                $itemsList = $groupedItems
                                    ->map(fn($item) => "{$item['name']} - ₦" . number_format($item['amount'], 2))
                                    ->join($groupedItems->count() > 1 ? ',<br>' : '');

                                // Format payment details
                                $paymentDetails = $existingPayments->map(function ($payment) {
                                    return sprintf(
                                        "Reference: %s\nPaid On: %s",
                                        $payment->reference,
                                        $payment->paid_at->format('j M, Y')
                                    );
                                })->join("\n\n");

                                // Get session and term names
                                $academicSession = AcademicSession::find($data['academic_session_id']);
                                $term = Term::find($data['term_id']);

                                // Build the message
                                $message = sprintf(
                                    "%s (%s) has already made payment for the following items for %s - %s:\n\n%s\n\nPayment Details:\n%s",
                                    $record->full_name,
                                    $record->classRoom->name,
                                    $academicSession->name,
                                    $term->name,
                                    $itemsList,
                                    $paymentDetails
                                );

                                // Show error notification
                                Notification::make()
                                    ->danger()
                                    ->title('Duplicate Payment Detected')
                                    ->body($message)
                                    ->actions([
                                        Action::make('view_payment_details')
                                            ->label('View Payment History')
                                            ->url(PaymentResource::getUrl('index', [
                                                'tenant' => $tenant,
                                                'tableFilters' => [
                                                    'student_id' => $record->id,
                                                    'academic_session_id' => $data['academic_session_id'],
                                                    'term_id' => $data['term_id'],
                                                ]
                                            ]))
                                            ->button(),
                                    ])
                                    ->persistent()
                                    ->send();

                                return; // Stop execution if duplicates found
                            }

                            // If no duplicates, proceed with payment creation
                            $totalAmount = collect($data['payment_items'])->sum('amount');
                            $totalDeposit = collect($data['payment_items'])->sum('deposit');
                            $totalBalance = collect($data['payment_items'])->sum('balance');

                            $payment = DB::transaction(function () use ($tenant, $record, $data, $totalAmount, $totalDeposit, $totalBalance) {
                                // Create main payment record
                                $payment = $record->payments()->create([
                                    'school_id' => $tenant->id,
                                    'receiver_id' => Auth::id(),
                                    'academic_session_id' => $data['academic_session_id'],
                                    'term_id' => $data['term_id'],
                                    'payment_method_id' => $data['payment_method_id'],
                                    'class_room_id' => $record->class_room_id,
                                    'status_id' => Status::where('type', 'payment')
                                        ->where('name', $totalDeposit >= $totalAmount ? 'Paid' : ($totalDeposit > 0 ? 'Partial' : 'Pending'))
                                        ->first()?->id,
                                    'amount' => $totalAmount,
                                    'deposit' => $totalDeposit,
                                    'balance' => $totalBalance,
                                    'reference' => $data['reference'],
                                    'payer_name' => $data['payer_name'],
                                    'payer_phone_number' => $data['payer_phone_number'],
                                    'remark' => $data['remark'],
                                    'meta_data' => $data['meta_data'] ?? null,
                                    'due_date' => $data['due_date'],
                                    'paid_at' => $data['paid_at'],
                                    'created_by' => Auth::id(),
                                    'updated_by' => Auth::id(),
                                ]);

                                // Create payment items
                                foreach ($data['payment_items'] as $item) {
                                    $payment->paymentItems()->create([
                                        'payment_type_id' => $item['payment_type_id'],
                                        'amount' => $item['amount'],
                                        'deposit' => $item['deposit'],
                                        'balance' => $item['balance'],
                                    ]);
                                }

                                return $payment;
                            });

                            // Show success notification
                            Notification::make()
                                ->success()
                                ->title('Payment Recorded')
                                ->body("Payment of ₦" . number_format($totalDeposit, 2) . " has been recorded successfully.")
                                ->actions([
                                    Action::make('view_receipt')
                                        ->label('View Receipt')
                                        ->url(fn() => PaymentResource::getUrl('view', [
                                            'tenant' => $tenant,
                                            'record' => $payment->id
                                        ]))
                                        ->button()
                                        ->openUrlInNewTab(),
                                ])
                                ->persistent()
                                ->send();
                        }),


                    // Change Status Action
                    Tables\Actions\Action::make('changeStatus')
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
                                ->maxLength(255)
                                ->required(),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Change Student Status')
                        ->modalDescription(fn(Student $record) => "Change status for {$record->admission->full_name}")
                        ->action(function (Student $record, array $data): void {
                            DB::transaction(function () use ($record, $data) {
                                // Create status change record
                                $record->statusChanges()->create([
                                    'from_status_id' => $record->status_id,
                                    'to_status_id' => $data['status_id'],
                                    'reason' => $data['reason'],
                                    'changed_by' => auth()->id(),
                                    'changed_at' => now(),
                                ]);

                                // Update student status
                                $record->update([
                                    'status_id' => $data['status_id'],
                                ]);

                                Notification::make()
                                    ->success()
                                    ->title('Status Updated')
                                    ->body('The student status has been updated successfully.')
                                    ->send();
                            });
                        }),

                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    // Bulk Promote Action
                    Tables\Actions\BulkAction::make('bulkPromote')
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
                                        'status_id' => Status::where('name', 'Promoted')->first()?->id,
                                    ]);

                                    StudentMovement::create([
                                        'student_id' => $record->id,
                                        'from_class_id' => $oldClassId,
                                        'to_class_id' => $data['class_room_id'],
                                        'from_session_id' => $currentSession->id,
                                        'to_session_id' => $nextSession->id,
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
                                $currentSession = config('app.current_session');
                                $newStatus = Status::find($data['status_id'])->name;

                                $records->each(function ($record) use ($data, $currentSession, $newStatus) {
                                    $oldStatus = $record->status->name;
                                    $oldClassId = $record->class_room_id;

                                    // Create status change record
                                    $record->statusChanges()->create([
                                        'from_status_id' => $record->status_id,
                                        'to_status_id' => $data['status_id'],
                                        'reason' => $data['reason'],
                                        'changed_by' => auth()->id(),
                                        'changed_at' => now(),
                                    ]);

                                    // Update student status
                                    $record->update([
                                        'status_id' => $data['status_id'],
                                    ]);
                                });

                                Notification::make()
                                    ->success()
                                    ->title('Status Updated')
                                    ->body('Selected students statuses have been updated successfully.')
                                    ->send();
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
