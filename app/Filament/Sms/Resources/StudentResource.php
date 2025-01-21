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
use App\Services\StudentStatusService;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
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
                                        ->options(fn() => PaymentMethod::where('active', true)
                                            ->pluck('name', 'id'))
                                        ->required(),

                                    Forms\Components\Select::make('payment_type_ids')
                                        ->label('Payment Types')
                                        ->multiple()
                                        ->preload()
                                        ->options(fn() => PaymentType::where('school_id', Filament::getTenant()?->id ?? 0)
                                            ->where('active', true)
                                            ->pluck('name', 'id')
                                            ->toArray() ?? [])
                                        ->required()
                                        ->live(debounce: 500)
                                        ->afterStateUpdated(function (Get $get, Set $set, ?array $state) {
                                            $currentItems = collect($get('payment_items') ?? []);
                                            $selectedIds = collect($state ?? []);

                                            // Add new items
                                            $newIds = $selectedIds->diff($currentItems->pluck('payment_type_id') ?? collect());
                                            $newItems = PaymentType::with('inventory')
                                                ->whereIn('id', $newIds)
                                                ->get()
                                                ->map(function ($type) {
                                                    $baseItem = [
                                                        'payment_type_id' => $type->id,
                                                        'item_amount' => $type->amount,
                                                        'item_deposit' => $type->amount,
                                                        'item_balance' => 0,
                                                    ];

                                                    if ($type->category === 'physical_item') {
                                                        $baseItem['quantity'] = 1;
                                                        $baseItem['has_quantity'] = true;
                                                        $baseItem['max_quantity'] = $type->inventory?->quantity ?? 0;
                                                        $baseItem['unit_price'] = $type->amount;
                                                    }

                                                    return $baseItem;
                                                })->toArray();

                                            // Remove deselected items
                                            $remainingItems = $currentItems
                                                ->filter(fn($item) => $selectedIds->contains($item['payment_type_id']))
                                                ->toArray();

                                            // Merge and update items
                                            $allItems = array_merge($remainingItems, $newItems);
                                            $set('payment_items', $allItems);

                                            // Calculate totals
                                            $totalAmount = collect($allItems)->sum('item_amount');
                                            $totalDeposit = collect($allItems)->sum('item_deposit');
                                            $totalBalance = collect($allItems)->sum('item_balance');

                                            $set('total_amount', $totalAmount);
                                            $set('total_deposit', $totalDeposit);
                                            $set('total_balance', $totalBalance);
                                        }),

                                    Forms\Components\Repeater::make('payment_items')
                                        ->schema([
                                            Forms\Components\Select::make('payment_type_id')
                                                ->label('Payment type')
                                                ->options(fn() => PaymentType::where('school_id', Filament::getTenant()?->id ?? 0)
                                                    ->where('active', true)
                                                    ->pluck('name', 'id')
                                                    ->toArray() ?? [])
                                                ->disabled()
                                                ->dehydrated()
                                                ->required(),

                                            Forms\Components\TextInput::make('quantity')
                                                ->label('Quantity')
                                                ->numeric()
                                                ->minValue(1)
                                                ->visible(fn(Get $get) => $get('has_quantity'))
                                                ->live()
                                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                    if ($get('has_quantity') && $state) {
                                                        // Validate against max quantity
                                                        $maxQuantity = $get('max_quantity') ?? 0;
                                                        if ((int)$state > $maxQuantity) {
                                                            $state = $maxQuantity;
                                                            $set('quantity', $maxQuantity);
                                                        }

                                                        // Calculate new amount
                                                        $unitPrice = $get('unit_price');
                                                        $amount = $unitPrice * (int)$state;
                                                        $set('item_amount', $amount);

                                                        if (!$get('../../enable_partial_payment')) {
                                                            $set('item_deposit', $amount);
                                                            $set('item_balance', 0);
                                                        }

                                                        // Calculate totals
                                                        $items = collect($get('../../payment_items'));
                                                        $totalAmount = $items->sum('item_amount');
                                                        $totalDeposit = $items->sum('item_deposit');
                                                        $totalBalance = $items->sum('item_balance');

                                                        $set('../../total_amount', $totalAmount);
                                                        $set('../../total_deposit', $totalDeposit);
                                                        $set('../../total_balance', $totalBalance);
                                                    }
                                                })
                                                ->suffixAction(
                                                    Forms\Components\Actions\Action::make('stockInfo')
                                                        ->icon('heroicon-m-information-circle')
                                                        ->tooltip(fn(Get $get) => 'Available stock: ' . ($get('max_quantity') ?? 0))
                                                        ->visible(fn(Get $get) => $get('has_quantity'))
                                                ),

                                            Forms\Components\Hidden::make('has_quantity'),
                                            Forms\Components\Hidden::make('max_quantity'),
                                            Forms\Components\Hidden::make('unit_price'),

                                            Forms\Components\TextInput::make('item_amount')
                                                ->label('Amount')
                                                ->numeric()
                                                ->prefix('₦')
                                                ->required()
                                                ->disabled()
                                                ->dehydrated(),

                                            Forms\Components\TextInput::make('item_deposit')
                                                ->label('Deposit')
                                                ->numeric()
                                                ->prefix('₦')
                                                ->required()
                                                ->live(debounce: 500)
                                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                    $amount = floatval($get('item_amount'));
                                                    $deposit = floatval($state ?? 0);

                                                    if ($deposit > $amount) {
                                                        $deposit = $amount;
                                                        $set('item_deposit', $amount);
                                                    }

                                                    $balance = max(0, $amount - $deposit);
                                                    $set('item_balance', $balance);

                                                    // Calculate totals
                                                    $items = collect($get('../../payment_items'));
                                                    $totalAmount = $items->sum('item_amount');
                                                    $totalDeposit = $items->sum('item_deposit');
                                                    $totalBalance = $items->sum('item_balance');

                                                    $set('../../total_amount', $totalAmount);
                                                    $set('../../total_deposit', $totalDeposit);
                                                    $set('../../total_balance', $totalBalance);
                                                }),

                                            Forms\Components\TextInput::make('item_balance')
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
                                                $items = $get('payment_items');
                                                foreach ($items as $key => $item) {
                                                    $set("payment_items.{$key}.item_deposit", $item['item_amount']);
                                                    $set("payment_items.{$key}.item_balance", 0);
                                                }
                                                $totalAmount = collect($items)->sum('item_amount');
                                                $set('total_deposit', $totalAmount);
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

                            // Your existing duplicate payment check...
                            if ($existingPayments->isNotEmpty()) {
                                // Your existing duplicate payment notification...
                                return;
                            }

                            // Process payment creation
                            DB::transaction(function () use ($tenant, $record, $data) {
                                $items = collect($data['payment_items']);
                                $totalAmount = $items->sum('item_amount');
                                $totalDeposit = $items->sum('item_deposit');
                                $totalBalance = $items->sum('item_balance');

                                // Create main payment record
                                $payment = $record->payments()->create([
                                    'school_id' => $tenant->id,
                                    'receiver_id' => Auth::id(),
                                    'academic_session_id' => $data['academic_session_id'],
                                    'term_id' => $data['term_id'],
                                    'payment_method_id' => $data['payment_method_id'],
                                    'class_room_id' => $record->class_room_id,
                                    'status_id' => Status::where('type', 'payment')
                                        ->where('name', $totalDeposit >= $totalAmount ? 'paid' : ($totalDeposit > 0 ? 'partial' : 'pending'))
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

                                // Create payment items and handle inventory
                                foreach ($data['payment_items'] as $item) {
                                    // Get payment type with inventory
                                    $paymentType = PaymentType::with('inventory')
                                        ->find($item['payment_type_id']);

                                    // Create payment item
                                    $paymentItem = $payment->paymentItems()->create([
                                        'payment_type_id' => $item['payment_type_id'],
                                        'amount' => $item['item_amount'],
                                        'deposit' => $item['item_deposit'],
                                        'balance' => $item['item_balance'],
                                        'quantity' => isset($item['has_quantity']) && $item['has_quantity'] ? $item['quantity'] : null,
                                        'unit_price' => isset($item['has_quantity']) && $item['has_quantity'] ? $item['unit_price'] : null,
                                    ]);

                                    // Handle inventory for physical items
                                    if (
                                        $paymentType &&
                                        $paymentType->category === 'physical_item' &&
                                        $paymentType->inventory &&
                                        isset($item['quantity'])
                                    ) {

                                        // Validate stock availability
                                        if ($paymentType->inventory->quantity < $item['quantity']) {
                                            throw new \Exception("Insufficient stock for {$paymentType->name}");
                                        }

                                        // Create inventory transaction
                                        $paymentType->inventory->transactions()->create([
                                            'school_id' => $tenant->id,
                                            'type' => 'OUT',
                                            'quantity' => $item['quantity'],
                                            'reference_type' => 'payment',
                                            'reference_id' => $payment->id,
                                            'note' => "Sold to {$record->full_name}",
                                            'created_by' => auth()->id(),
                                        ]);

                                        // Update inventory quantity
                                        $paymentType->inventory->decrement('quantity', $item['quantity']);
                                    }
                                }

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
                            });
                        }),
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
