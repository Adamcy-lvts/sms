<?php

namespace App\Filament\Sms\Resources;

use Carbon\Carbon;
use Filament\Forms;
use App\Models\Term;
use Filament\Tables;
use App\Models\Status;
use App\Models\Payment;
use App\Models\Student;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use App\Models\ClassRoom;
use Filament\Tables\Table;
use App\Models\PaymentType;
use Filament\Support\RawJs;
use Filament\Actions\Action;
use App\Models\PaymentMethod;
use Filament\Facades\Filament;
use App\Models\AcademicSession;
use Filament\Resources\Resource;
use Filament\Actions\ExportAction;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\Filter;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Date;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Enums\FiltersLayout;
use App\Filament\Exports\PaymentExporter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\DateTimePicker;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Actions\Exports\Enums\ExportFormat;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\PaymentResource\Pages;
use App\Filament\Sms\Resources\PaymentResource\RelationManagers;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Financial Management';
    protected static ?int $navigationSort = 3;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Main Grid Layout - 1/3 : 2/3 split
                Grid::make([
                    'default' => 1,
                    'sm' => 1,
                    'lg' => 3,
                ])->schema([
                    // Left Column (1/3 width) - Primary Information
                    Forms\Components\Group::make([
                        // Student Search Section
                        Forms\Components\Section::make('Search Student')
                            ->description('Select class room and search for a student')
                            ->schema([
                                Forms\Components\Select::make('class_room_id')
                                    ->label('Class Room')
                                    ->options(fn() => ClassRoom::where('school_id', Filament::getTenant()?->id ?? 0)
                                        ->orderBy('name')
                                        ->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->preload(),

                                Forms\Components\Select::make('student_id')
                                    ->label('Student')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->options(function (Get $get) {
                                        $classRoomId = $get('class_room_id');
                                        if (!$classRoomId) return [];

                                        return Student::query()
                                            ->where('class_room_id', $classRoomId)
                                            ->orderBy('first_name')
                                            ->get()
                                            ->mapWithKeys(function ($student) {
                                                return [
                                                    $student->id => sprintf(
                                                        "%s %s %s",
                                                        $student->first_name ?? '',
                                                        $student->middle_name ?? '',
                                                        $student->last_name ?? ''
                                                    )
                                                ];
                                            });
                                    })
                                    ->getSearchResultsUsing(function (string $search, Get $get): array {
                                        $classRoomId = $get('class_room_id');
                                        if (!$classRoomId) return [];

                                        return Student::query()
                                            ->where('class_room_id', $classRoomId)
                                            ->where(function ($query) use ($search) {
                                                $query->where('first_name', 'like', "%{$search}%")
                                                    ->orWhere('middle_name', 'like', "%{$search}%")
                                                    ->orWhere('last_name', 'like', "%{$search}%")
                                                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                                                    ->orWhereRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                                                    ->orWhereHas('admission', function ($query) use ($search) {
                                                        $query->where('admission_number', 'like', "%{$search}%");
                                                    });
                                            })
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(function ($student) {
                                                return [
                                                    $student->id => sprintf(
                                                        "%s %s %s %s",
                                                        $student->first_name ?? '',
                                                        $student->middle_name ?? '',
                                                        $student->last_name ?? '',
                                                        $student->admission ? "({$student->admission->admission_number})" : ''
                                                    )
                                                ];
                                            })
                                            ->toArray();
                                    })
                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                        if ($state) {
                                            $student = Student::with(['admission'])->find($state);
                                            $set('payer_name', $student?->admission?->guardian_name ?? '');
                                            $set('payer_phone_number', $student?->admission?->guardian_phone_number ?? '');
                                        }
                                    })
                                    ->disabled(fn(Get $get) => !$get('class_room_id')),
                            ])->columns(1),

                        // Basic Payment Information
                        Forms\Components\Section::make('Payment Information')
                            ->schema([
                                Forms\Components\Select::make('academic_session_id')
                                    ->label('Academic Session')
                                    ->options(fn() => AcademicSession::where('school_id', Filament::getTenant()?->id ?? 0)
                                        ->pluck('name', 'id')
                                        ->toArray() ?? [])
                                    ->default(fn() => config('app.current_session')?->id ?? null)
                                    ->required()
                                    ->live(),

                                Forms\Components\Select::make('term_id')
                                    ->label('Term')
                                    ->options(fn(Get $get) => Term::where('academic_session_id', $get('academic_session_id'))->pluck('name', 'id'))
                                    ->default(fn() => config('app.current_term')->id ?? null)
                                    ->required(),

                                Forms\Components\Select::make('payment_method_id')
                                    ->label('Payment Method')
                                    ->required()
                                    ->options(fn() => PaymentMethod::where('active', true)
                                        ->pluck('name', 'id')),

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
                            ])->columns(1),

                        // Payment Status Section
                        Forms\Components\Section::make('Payment Status')
                            ->schema([
                                Forms\Components\TextInput::make('reference')
                                    ->default(fn() => 'PAY-' . strtoupper(uniqid()))
                                    ->readonly()
                                    ->dehydrated(),

                                Forms\Components\Select::make('status_id')
                                    ->label('Payment Status')
                                    ->options(fn() => Status::where('type', 'payment')->pluck('name', 'id'))
                                    ->default(fn() => Status::where('type', 'payment')
                                        ->where('name', 'paid')
                                        ->first()?->id)
                                    ->required(),

                                Forms\Components\DateTimePicker::make('paid_at')
                                    ->label('Payment Date')
                                    ->required()
                                    ->native(false)
                                    ->default(now()),

                                Forms\Components\DateTimePicker::make('due_date')
                                    ->label('Due Date')
                                    ->required(function (Get $get) {
                                        $selectedIds = $get('payment_type_ids') ?? [];
                                        return PaymentType::whereIn('id', $selectedIds)
                                            ->where('has_due_date', true)
                                            ->exists();
                                    })
                                    ->helperText(function ($state) {
                                        if (!$state) return null;

                                        $dueDate = Carbon::parse($state);
                                        // $daysLeft = now()->diffInDays($dueDate, false);
                                        // Use abs() to get absolute value and avoid negative numbers
                                        $daysLeft = abs(now()->startOfDay()->diffInDays($dueDate->startOfDay()));

                                        return $daysLeft < 0
                                            ? "Payment is overdue by " . abs($daysLeft) . " days"
                                            : "Payment is due in " . $daysLeft . " days";
                                    })
                                    ->visible(function (Get $get) {
                                        $selectedIds = $get('payment_type_ids') ?? [];
                                        return PaymentType::whereIn('id', $selectedIds)
                                            ->where('has_due_date', true)
                                            ->exists();
                                    })
                                    ->native(false)
                            ])->columns(1),
                    ])->columnSpan(['lg' => 1]),

                    // Right Column (2/3 width) - Payment Details and Calculations
                    Forms\Components\Group::make([
                        Forms\Components\Section::make('Payment Details')
                            ->description('Select payment types and enter amount details')
                            ->schema([
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
                                        $term = Term::find($get('term_id'));

                                        // Add new items
                                        $newIds = $selectedIds->diff($currentItems->pluck('payment_type_id') ?? collect());
                                        $newItems = PaymentType::with('inventory')
                                            ->whereIn('id', $newIds)
                                            ->get()
                                            ->map(function ($type) use ($term, $set) {
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
                                                // Get and set due date if term exists
                                                // Only set due date for payment types that require it
                                                if ($term && $type->hasDueDate()) {
                                                    $set('due_date', $type->getDueDate($term)?->format('Y-m-d H:i:s'));
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
                                            ->required()
                                            ->preload()
                                            ->searchable()
                                            ->live()
                                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                if ($state) {
                                                    $paymentType = PaymentType::with('inventory')->find($state);
                                                    if ($paymentType) {
                                                        // Set base amounts
                                                        $set('item_amount', $paymentType->amount);
                                                        $set('item_deposit', $paymentType->amount);
                                                        $set('item_balance', 0);

                                                        if ($paymentType->category === 'physical_item') {
                                                            $set('has_quantity', true);
                                                            $set('quantity', 1);
                                                            $set('max_quantity', $paymentType->inventory?->quantity ?? 0);
                                                            $set('unit_price', $paymentType->amount);
                                                        } else {
                                                            $set('has_quantity', false);
                                                            $set('quantity', null);
                                                            $set('max_quantity', null);
                                                            $set('unit_price', null);
                                                        }

                                                        // Calculate totals
                                                        $items = collect($get('../../payment_items'))->map(function ($item) use ($state, $paymentType) {
                                                            if ($item['payment_type_id'] === $state) {
                                                                $item['item_amount'] = $paymentType->amount;
                                                                $item['item_deposit'] = $paymentType->amount;
                                                                $item['item_balance'] = 0;
                                                            }
                                                            return $item;
                                                        });

                                                        // Update totals
                                                        $totalAmount = $items->sum('item_amount');
                                                        $totalDeposit = $items->sum('item_deposit');
                                                        $totalBalance = $items->sum('item_balance');

                                                        $set('../../total_amount', $totalAmount);
                                                        $set('../../total_deposit', $totalDeposit);
                                                        $set('../../total_balance', $totalBalance);
                                                    }
                                                }
                                            }),

                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->minValue(1)
                                            ->visible(fn(Get $get) => $get('has_quantity'))
                                            ->live()
                                            ->afterStateUpdated(function (Get $get, Set $set, $state) {

                                                // Validate against max quantity immediately
                                                $maxQuantity = $get('max_quantity') ?? 0;
                                                if ((int)$state > $maxQuantity) {
                                                    Notification::make()
                                                        ->warning()
                                                        ->title('Exceeds Available Stock')
                                                        ->body("Maximum available quantity is {$maxQuantity}")
                                                        ->send();

                                                    $set('quantity', $maxQuantity);
                                                    $state = $maxQuantity;
                                                }
                                                if ($get('has_quantity') && $state) {
                                                    // Get the payment type to get the current unit price
                                                    $paymentType = PaymentType::find($get('payment_type_id'));

                                                    // Get current unit price (either from form state or payment type)
                                                    $unitPrice = floatval($get('unit_price') ?? $paymentType->amount ?? 0);

                                                    // Calculate new amount based on quantity
                                                    $quantity = (int)$state;
                                                    $amount = $unitPrice * $quantity;

                                                    // Set the amount
                                                    $set('item_amount', $amount);

                                                    // If not partial payment, set deposit equal to amount
                                                    if (!$get('../../enable_partial_payment')) {
                                                        $set('item_deposit', $amount);
                                                    }

                                                    // Calculate balance
                                                    $deposit = floatval($get('item_deposit') ?? $amount);
                                                    $balance = max(0, $amount - $deposit);
                                                    $set('item_balance', $balance);

                                                    // Calculate and update totals
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
                                            ->live()
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
                                    ->columns(4)
                                    ->columnSpanFull()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        // Update payment_type_ids to match current items
                                        $currentTypeIds = collect($state ?? [])->pluck('payment_type_id')->filter()->values()->toArray();
                                        $set('payment_type_ids', $currentTypeIds);

                                        // Calculate totals
                                        $items = collect($state ?? []);
                                        $totalAmount = $items->sum('item_amount');
                                        $totalDeposit = $items->sum('item_deposit');
                                        $totalBalance = $items->sum('item_balance');

                                        $set('total_amount', $totalAmount);
                                        $set('total_deposit', $totalDeposit);
                                        $set('total_balance', $totalBalance);
                                    })
                                    ->itemLabel(function (array $state): ?string {
                                        return PaymentType::find($state['payment_type_id'] ?? null)?->name ?? 'Payment Item';
                                    }),

                                // Your existing totals grid
                                Forms\Components\Grid::make(3)->schema([
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

                        // Payer Information Section
                        Forms\Components\Section::make('Payer Information')
                            ->schema([
                                Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('payer_name')
                                        ->label('Payer Name')
                                        ->required(),

                                    Forms\Components\TextInput::make('payer_phone_number')
                                        ->label('Payer Phone')
                                        ->tel(),
                                ]),

                                Forms\Components\Textarea::make('remark')
                                    ->label('Payment Remark')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),

                        // Add this inside the Payer Information Section
                        Forms\Components\Section::make('Receipt Customization')
                            ->description('Customize the terms and notes that appear on the receipt')
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                Forms\Components\KeyValue::make('meta_data.terms')
                                    ->label('Receipt Terms')
                                    ->addActionLabel('Add Term')
                                    ->keyLabel('Order')
                                    ->valueLabel('Term')
                                    ->default([
                                        '1' => 'Payment is non-refundable.',
                                        '2' => 'Please keep this receipt for your records.'
                                    ])
                                    ->reorderable()
                                    ->columnSpanFull()
                                    ->rules(['array'])
                                    ->afterStateHydrated(function (Forms\Components\KeyValue $component, $state) {
                                        if (empty($state)) {
                                            $component->state([
                                                '1' => 'Payment is non-refundable.',
                                                '2' => 'Please keep this receipt for your records.'
                                            ]);
                                        }
                                    }),

                                Forms\Components\Toggle::make('meta_data.show_terms')
                                    ->label('Show Terms on Receipt')
                                    ->default(true)
                                    ->inline(false),
                            ])
                            ->icon('heroicon-o-document-text')
                            ->columns(1),
                    ])->columnSpan(['lg' => 2]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('student.full_name')
                    ->label('Student')
                    ->sortable()
                    ->searchable(['first_name', 'last_name'])
                    ->formatStateUsing(fn($record) => sprintf(
                        "%s %s",
                        $record?->student?->first_name ?? '',
                        $record?->student?->last_name ?? ''
                    ) ?: 'N/A'),

                Tables\Columns\TextColumn::make('classRoom.name')
                    ->label('Class Room')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn($state) => $state ?? 'Unassigned'),

                Tables\Columns\TextColumn::make('academicSession.name')
                    ->label('Session')
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state ?? '-'),

                Tables\Columns\TextColumn::make('term.name')
                    ->label('Term')
                    ->sortable(),

                Tables\Columns\TextColumn::make('paymentItems.paymentType.name')
                    ->label('Payment Types')
                    ->formatStateUsing(function ($record) {
                        return $record?->paymentItems
                            ->map(fn($item) => $item->paymentType?->name ?? 'Unknown')
                            ->filter()
                            ->join(', ') ?: 'No Items';
                    })
                    ->wrap()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->formatStateUsing(fn($state) => formatNaira($state ?? 0))
                    ->sortable(),

                Tables\Columns\TextColumn::make('deposit')
                    ->formatStateUsing(fn($state) => formatNaira($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance')
                    ->formatStateUsing(fn($state) => formatNaira($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('status.name')
                    ->badge()
                    ->formatStateUsing(function ($state, $record) {
                        $label = $state;
                        if ($record->is_balance_payment) {
                            $label .= ' (Balance Payment)';
                        }
                        return $label;
                    })
                    ->color(fn(string $state, $record): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'pending' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->label('Method')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('due_date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('paid_at', 'desc')
            ->filters([

                // Student Filter - For filtering payments by student
                Tables\Filters\SelectFilter::make('student_id')
                    ->multiple()
                    ->searchable()  // Enable searching through students
                    ->label('Student')
                    ->preload()    // Preload options for better performance
                    // Get student options with formatted display
                    ->getSearchResultsUsing(function (string $search): array {
                        return Student::query()
                            ->where('school_id', Filament::getTenant()->id)
                            ->where(function ($query) use ($search) {
                                $query->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhereHas('admission', function ($query) use ($search) {
                                        $query->where('admission_number', 'like', "%{$search}%");
                                    });
                            })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($student) {
                                // Format: Student Name - Class (Admission Number)
                                return [
                                    $student->id => $student->full_name . ' - ' .
                                        $student->classRoom->name .
                                        ($student->admission ? ' (' . $student->admission->admission_number . ')' : '')
                                ];
                            })
                            ->toArray();
                    })
                    // Get option label for selected values
                    ->getOptionLabelUsing(function ($value): ?string {
                        $student = Student::query()
                            ->with(['classRoom', 'admission'])
                            ->find($value);

                        if (!$student) return null;

                        return $student->full_name . ' - ' .
                            $student->classRoom->name .
                            ($student->admission ? ' (' . $student->admission->admission_number . ')' : '');
                    })
                    // Apply the filter to the query
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            !empty($data['values']),
                            fn(Builder $query) => $query->whereIn('student_id', $data['values'])
                        );
                    })
                    // Show active filter indicators
                    ->indicateUsing(function (array $state): array {
                        if (empty($state['values'] ?? [])) {
                            return [];
                        }

                        // Get selected students info
                        $students = Student::whereIn('id', $state['values'])
                            ->get()
                            ->map(function ($student) {
                                return "{$student->full_name} ({$student->classRoom->name})";
                            });

                        return ["Students: " . $students->join(', ')];
                    }),

                // Academic Session Filter
                Tables\Filters\SelectFilter::make('academic_session_id')
                    ->multiple()
                    ->label('Academic Session')
                    ->options(fn() => AcademicSession::query()
                        ->where('school_id', Filament::getTenant()->id)
                        ->pluck('name', 'id'))
                    ->default(config('app.current_session')->id ?? null)
                    ->preload(),

                // Term Filter
                Tables\Filters\SelectFilter::make('term_id')
                    ->label('Term')
                    ->options(fn() => Term::query()
                        ->whereHas('academicSession', function ($query) {
                            $query->where('school_id', Filament::getTenant()->id);
                        })
                        ->with('academicSession')
                        ->get()
                        ->mapWithKeys(fn($term) => [
                            $term->id => $term->academicSession->name . ' - ' . $term->name
                        ]))
                    ->default(config('app.current_term')->id ?? null)
                    ->preload(),

                // Class Filters Group
                Tables\Filters\Filter::make('class_filters')
                    ->form([
                        Forms\Components\Select::make('student_class')
                            ->multiple()
                            ->label('Student Class')
                            ->options(fn() => ClassRoom::where('school_id', Filament::getTenant()->id)
                                ->orderBy('name')
                                ->pluck('name', 'id')),

                        Forms\Components\Select::make('payment_class')
                            ->multiple()
                            ->label('Payment Class')
                            ->options(fn() => ClassRoom::where('school_id', Filament::getTenant()->id)
                                ->orderBy('name')
                                ->pluck('name', 'id')),
                    ])
                    ->query(function (Builder $query, array $state): Builder {
                        return $query
                            ->when(
                                !empty($state['student_class']),
                                fn(Builder $query) => $query->whereHas('student', function ($query) use ($state) {
                                    $query->whereIn('class_room_id', $state['student_class']);
                                })
                            )
                            ->when(
                                !empty($state['payment_class']),
                                fn(Builder $query) => $query->whereIn('payments.class_room_id', $state['payment_class'])
                            );
                    })
                    ->indicateUsing(function (array $state): array {
                        $indicators = [];

                        if (!empty($state['student_class'])) {
                            $classes = ClassRoom::whereIn('id', $state['student_class'])->pluck('name');
                            $indicators[] = 'Student Class: ' . $classes->join(', ');
                        }

                        if (!empty($state['payment_class'])) {
                            $classes = ClassRoom::whereIn('id', $state['payment_class'])->pluck('name');
                            $indicators[] = 'Payment Class: ' . $classes->join(', ');
                        }

                        return $indicators;
                    }),

                Tables\Filters\Filter::make('payment_type')
                    ->form([
                        Forms\Components\Select::make('payment_types')
                            ->multiple()
                            ->label('Payment Types')
                            ->options(fn() => PaymentType::where('school_id', Filament::getTenant()->id)
                                ->where('active', true)
                                ->pluck('name', 'id'))
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['payment_types'] ?? null, function ($query) use ($data) {
                            $query->whereHas('paymentItems', function ($query) use ($data) {
                                $query->whereIn('payment_type_id', $data['payment_types']);
                            });
                        });
                    }),

                // Payment Status Filter
                Tables\Filters\SelectFilter::make('status_id')
                    ->multiple()
                    ->label('Payment Status')
                    ->options(fn() => Status::where('type', 'payment')
                        ->pluck('name', 'id'))
                    ->preload(),

                // Balance Status Filter
                Tables\Filters\Filter::make('balance_status')
                    ->form([
                        Forms\Components\Select::make('balance_type')
                            ->label('Balance Status')
                            ->multiple()
                            ->options([
                                'no_balance' => 'Fully Paid (No Balance)',
                                'has_balance' => 'Has Outstanding Balance',
                                'overdue' => 'Overdue Payments',
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['balance_type'] ?? null, function ($query) use ($data) {
                            $conditions = collect($data['balance_type']);

                            $query->where(function ($q) use ($conditions) {
                                if ($conditions->contains('no_balance')) {
                                    $q->orWhere('balance', 0);
                                }
                                if ($conditions->contains('has_balance')) {
                                    $q->orWhere('balance', '>', 0);
                                }
                                if ($conditions->contains('overdue')) {
                                    $q->orWhere(function ($subQ) {
                                        $subQ->where('balance', '>', 0)
                                            ->where('due_date', '<', now());
                                    });
                                }
                            });
                        });
                    }),

                // Balance Range Filter
                Tables\Filters\Filter::make('balance_range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('min_balance')
                                    ->label('Minimum Balance')
                                    ->numeric()
                                    ->prefix('₦'),
                                Forms\Components\TextInput::make('max_balance')
                                    ->label('Maximum Balance')
                                    ->numeric()
                                    ->prefix('₦'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_balance'] ?? null,
                                fn(Builder $query, $min): Builder => $query->where('balance', '>=', $min)
                            )
                            ->when(
                                $data['max_balance'] ?? null,
                                fn(Builder $query, $max): Builder => $query->where('balance', '<=', $max)
                            );
                    }),

                // Due Date Filter
                Tables\Filters\Filter::make('due_date_status')
                    ->form([
                        Forms\Components\Select::make('due_status')
                            ->label('Due Date Status')
                            ->multiple()
                            ->options([
                                'overdue' => 'Overdue',
                                'due_today' => 'Due Today',
                                'due_this_week' => 'Due This Week',
                                'due_next_week' => 'Due Next Week',
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['due_status'] ?? null, function ($query) use ($data) {
                            $query->where(function ($q) use ($data) {
                                foreach ($data['due_status'] as $status) {
                                    switch ($status) {
                                        case 'overdue':
                                            $q->orWhere(function ($sq) {
                                                $sq->where('due_date', '<', now()->startOfDay())
                                                    ->where('balance', '>', 0);
                                            });
                                            break;
                                        case 'due_today':
                                            $q->orWhereBetween('due_date', [
                                                now()->startOfDay(),
                                                now()->endOfDay()
                                            ]);
                                            break;
                                        case 'due_this_week':
                                            $q->orWhereBetween('due_date', [
                                                now()->startOfWeek(),
                                                now()->endOfWeek()
                                            ]);
                                            break;
                                        case 'due_next_week':
                                            $q->orWhereBetween('due_date', [
                                                now()->addWeek()->startOfWeek(),
                                                now()->addWeek()->endOfWeek()
                                            ]);
                                            break;
                                    }
                                }
                            });
                        });
                    }),

                // Payment Date Range Filter
                Tables\Filters\Filter::make('payment_date')
                    ->form([
                        Forms\Components\DatePicker::make('paid_from')
                            ->label('Paid From'),
                        Forms\Components\DatePicker::make('paid_until')
                            ->label('Paid Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['paid_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('paid_at', '>=', $date),
                            )
                            ->when(
                                $data['paid_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('paid_at', '<=', $date),
                            );
                    }),

                // Amount Range Filter
                Tables\Filters\Filter::make('amount_range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('min_amount')
                                    ->label('Minimum Amount')
                                    ->numeric()
                                    ->prefix('₦'),
                                Forms\Components\TextInput::make('max_amount')
                                    ->label('Maximum Amount')
                                    ->numeric()
                                    ->prefix('₦'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_amount'] ?? null,
                                fn(Builder $query, $min): Builder => $query->where('amount', '>=', $min)
                            )
                            ->when(
                                $data['max_amount'] ?? null,
                                fn(Builder $query, $max): Builder => $query->where('amount', '<=', $max)
                            );
                    }),
            ])
            // ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\Action::make('recordBalance')
                        ->icon('heroicon-o-currency-dollar')
                        ->color('success')
                        ->label('Record Balance')
                        ->modalHeading('Record Balance Payment')
                        ->modalDescription(fn(Payment $record) => "Recording additional payment for {$record->student->full_name}")
                        ->requiresConfirmation()
                        ->visible(fn(Payment $record) => $record->balance > 0)
                        ->form(function (Payment $record): array {
                            $paymentItems = $record->paymentItems->where('balance', '>', 0);
                            $schema = [];

                            // Add current payment details section
                            $schema[] = Section::make('Current Payment Details')
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('current_amount')
                                                ->label('Total Amount')
                                                ->prefix('₦')
                                                ->disabled()
                                                ->default($record->amount),

                                            TextInput::make('current_deposit')
                                                ->label('Amount Paid')
                                                ->prefix('₦')
                                                ->disabled()
                                                ->default($record->deposit),

                                            TextInput::make('current_balance')
                                                ->label('Outstanding Balance')
                                                ->prefix('₦')
                                                ->disabled()
                                                ->default($record->balance),
                                        ]),
                                ]);

                            // Add section for each payment item with balance
                            foreach ($paymentItems as $index => $item) {
                                $schema[] = Section::make($item->paymentType->name)
                                    ->schema([
                                        Hidden::make("items.{$index}.payment_type_id")
                                            ->default($item->payment_type_id),

                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make("items.{$index}.amount")
                                                    ->label('Outstanding Balance')
                                                    ->prefix('₦')
                                                    ->disabled()
                                                    ->default($item->balance),

                                                TextInput::make("items.{$index}.deposit")
                                                    ->label('Amount to Pay')
                                                    ->numeric()
                                                    ->prefix('₦')
                                                    ->required()
                                                    ->default(0)
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, Set $set) use ($item) {
                                                        $deposit = floatval($state);
                                                        if ($deposit > $item->balance) {
                                                            $set('deposit', $item->balance);
                                                        }
                                                    }),

                                                TextInput::make("items.{$index}.balance")
                                                    ->label('Remaining Balance')
                                                    ->prefix('₦')
                                                    ->disabled()
                                                    ->default(fn(Get $get) => max(0, $item->balance - floatval($get("items.{$index}.deposit")))),
                                            ]),
                                    ]);
                            }

                            // Add payment method section
                            $schema[] = Section::make('Payment Method')
                                ->schema([
                                    Select::make('payment_method_id')
                                        ->label('Payment Method')
                                        ->options(fn() => PaymentMethod::where('active', true)
                                            ->pluck('name', 'id'))
                                        ->required(),

                                    Textarea::make('remark')
                                        ->label('Payment Remark')
                                        ->rows(2),
                                ]);

                            return $schema;
                        })
                        ->modalWidth(MaxWidth::FiveExtraLarge)
                        ->action(function (array $data, Payment $record): void {
                            $totalDeposit = 0; // Define it outside the transaction

                            $balancePayment = DB::transaction(function () use ($data, $record, &$totalDeposit) { // Note the & to pass by reference
                                // Calculate total deposit first to use in both operations
                                $totalDeposit = collect($data['items'])->sum('deposit');
                                $newTotalDeposit = $record->deposit + $totalDeposit;
                                $newTotalBalance = $record->amount - $newTotalDeposit;

                                // Create the balance payment record first
                                $balancePayment = $record->student->payments()->create([
                                    'school_id' => Filament::getTenant()->id,
                                    'receiver_id' => auth()->id(),
                                    'academic_session_id' => $record->academic_session_id,
                                    'term_id' => $record->term_id,
                                    'class_room_id' => $record->class_room_id,
                                    'payment_method_id' => $data['payment_method_id'],
                                    'status_id' => Status::where('type', 'payment')->where('name', 'Paid')->first()?->id,
                                    'original_payment_id' => $record->id,
                                    'is_balance_payment' => true,
                                    'amount' => $totalDeposit,
                                    'deposit' => $totalDeposit,
                                    'balance' => 0,
                                    'reference' => 'PAY-' . strtoupper(uniqid()),
                                    'payer_name' => $record->payer_name,
                                    'payer_phone_number' => $record->payer_phone_number,
                                    'remark' => $data['remark'] ?? "Balance payment for {$record->reference}",
                                    'due_date' => now(),
                                    'paid_at' => now(),
                                    'created_by' => auth()->id(),
                                    'updated_by' => auth()->id(),
                                ]);

                                // Create payment items for the balance payment
                                foreach ($data['items'] as $item) {
                                    if (floatval($item['deposit']) > 0) {
                                        $balancePayment->paymentItems()->create([
                                            'payment_type_id' => $item['payment_type_id'],
                                            'amount' => floatval($item['deposit']),
                                            'deposit' => floatval($item['deposit']),
                                            'balance' => 0
                                        ]);
                                    }
                                }

                                // Update original payment items
                                foreach ($data['items'] as $item) {
                                    $paymentItem = $record->paymentItems
                                        ->where('payment_type_id', $item['payment_type_id'])
                                        ->first();

                                    if ($paymentItem) {
                                        $newDeposit = $paymentItem->deposit + floatval($item['deposit']);
                                        $newBalance = $paymentItem->amount - $newDeposit;

                                        $paymentItem->update([
                                            'deposit' => $newDeposit,
                                            'balance' => $newBalance
                                        ]);
                                    }
                                }

                                // Update original payment record
                                $record->update([
                                    'deposit' => $newTotalDeposit,
                                    'balance' => $newTotalBalance,
                                    'status_id' => Status::where('type', 'payment')
                                        ->where('name', $newTotalBalance <= 0 ? 'Paid' : 'Partial')
                                        ->first()?->id,
                                    'updated_by' => auth()->id(),
                                ]);

                                return $balancePayment;
                            });

                            Notification::make()
                                ->success()
                                ->title('Balance Payment Recorded')
                                ->body("Balance payment of ₦" . number_format($totalDeposit, 2) . " has been recorded successfully.")
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('view_receipt')
                                        ->label('View Receipt')
                                        ->url(fn() => PaymentResource::getUrl('view', ['record' => $balancePayment]))
                                        ->button()
                                        ->openUrlInNewTab(),
                                ])
                                ->persistent()
                                ->send();
                        }),
                    Tables\Actions\Action::make('cancel')
                        ->label('Cancel Payment')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Cancel Payment')
                        ->modalDescription('Are you sure you want to cancel this payment? This will return any physical items to stock.')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Cancellation Reason')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->action(function (Model $record, array $data): void {
                            // Get cancelled status
                            $cancelledStatus = Status::where('type', 'payment')
                                ->where('name', 'cancelled')
                                ->first();

                            if (!$cancelledStatus) {
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('Cancelled status not found in the system.')
                                    ->send();
                                return;
                            }

                            DB::transaction(function () use ($record, $data, $cancelledStatus) {
                                // Handle physical items and return to stock
                                foreach ($record->paymentItems as $item) {
                                    $paymentType = $item->paymentType;

                                    if (
                                        $paymentType &&
                                        $paymentType->category === 'physical_item' &&
                                        $paymentType->inventory &&
                                        $item->quantity
                                    ) {

                                        // Create inventory transaction for return
                                        $paymentType->inventory->transactions()->create([
                                            'school_id' => $record->school_id,
                                            'type' => 'IN',
                                            'quantity' => $item->quantity,
                                            'reference_type' => 'payment_cancellation',
                                            'reference_id' => $record->id,
                                            'note' => "Items returned due to payment cancellation: {$data['reason']}",
                                            'created_by' => auth()->id(),
                                        ]);

                                        // Return items to inventory
                                        $paymentType->inventory->increment('quantity', $item->quantity);
                                    }
                                }

                                // Update payment
                                $record->update([
                                    'status_id' => $cancelledStatus->id,
                                    'remark' => $data['reason'],
                                    'updated_by' => auth()->id(),
                                ]);

                                Notification::make()
                                    ->success()
                                    ->title('Payment Cancelled')
                                    ->body('Payment has been cancelled and items have been returned to stock.')
                                    ->send();
                            });
                        })
                        ->visible(
                            fn(Model $record): bool =>
                            // Only show for non-cancelled payments
                            $record->status?->name !== 'cancelled'
                        ),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])

            ->headerActions([
                Tables\Actions\ExportAction::make()
                    ->label('Export Payments')
                    ->exporter(PaymentExporter::class)
                    ->formats([
                        ExportFormat::Xlsx,
                        ExportFormat::Csv,
                    ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ExportBulkAction::make()
                        ->label('Export Selected')
                        ->exporter(PaymentExporter::class)
                        ->formats([
                            ExportFormat::Xlsx,
                            ExportFormat::Csv,
                        ])
                ]),
            ])
            ->emptyStateHeading('No payments found')
            ->emptyStateDescription('Start by recording a new payment.')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->striped()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->defaultPaginationPageOption(25);
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('created_at', '>=', now()->startOfDay())->count() ?: null;
    }

    public static function getNairaFormatter(): RawJs
    {
        return RawJs::make(<<<'JS'
        function (number) {
            number = parseFloat(number);
            if (isNaN(number)) return '';
            if (number % 1 === 0) {
                return '₦' + number.toLocaleString('en-US');
            } else {
                return '₦' + number.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }
    JS);
    }
}
