<?php

namespace App\Livewire;

use App\Models\Term;
use App\Models\Status;
use App\Models\Payment;
use App\Models\Student;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\ClassRoom;
use Filament\Tables\Table;
use App\Models\PaymentType;
use App\Models\PaymentMethod;
use Filament\Facades\Filament;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Sms\Resources\PaymentResource;
use Filament\Widgets\TableWidget as BaseWidget;

class StudentPaymentHistoryTable extends BaseWidget
{
    public ?Student $student = null;

    public function mount(?Student $student = null)
    {
        $this->student = $student ?? Filament::getTenant();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Payment::query()
                    ->where('student_id', $this->student->id)
                    ->latest('paid_at')
            )
            ->columns([
                TextColumn::make('reference')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('academicSession.name')
                    ->label('Session')
                    ->sortable(),

                TextColumn::make('term.name')
                    ->sortable(),

                TextColumn::make('classRoom.name')
                    ->label('Class Room')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('info'),


                TextColumn::make('paymentItems.paymentType.name')
                    ->label('Payment Types')
                    ->formatStateUsing(function ($record) {
                        return $record->paymentItems->map(function ($item) {
                            return $item->paymentType->name;
                        })->join(', ');
                    })
                    ->wrap()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->formatStateUsing(fn($state) => formatNaira($state))
                    ->sortable(),

                TextColumn::make('deposit')
                    ->formatStateUsing(fn($state) => formatNaira($state))
                    ->sortable(),

                TextColumn::make('balance')
                    ->formatStateUsing(fn($state) => formatNaira($state))
                    ->sortable(),

                TextColumn::make('status.name')
                    ->badge()
                    ->colors([
                        'success' => 'paid',
                        'warning' => 'partial',
                        'danger' => 'overdue',
                        'secondary' => 'pending',
                    ]),

                TextColumn::make('paymentMethod.name')
                    ->label('Method'),

                TextColumn::make('paid_at')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('paid_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->poll('30s')
            ->filters([

                // Academic Session Filter
                SelectFilter::make('academic_session_id')
                    ->multiple()
                    ->label('Academic Session')
                    ->options(fn() => AcademicSession::query()
                        ->where('school_id', Filament::getTenant()->id)
                        ->pluck('name', 'id'))
                    // ->default(config('app.current_session')->id ?? null)
                    ->preload(),

                // Term Filter
                SelectFilter::make('term_id')
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
                    // ->default(config('app.current_term')->id ?? null)
                    ->preload(),


                Filter::make('payment_type')
                    ->form([
                        Select::make('payment_types')
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
                SelectFilter::make('status_id')
                    ->multiple()
                    ->label('Payment Status')
                    ->options(fn() => Status::where('type', 'payment')
                        ->pluck('name', 'id'))
                    ->preload(),

                // Balance Status Filter
                Filter::make('balance_status')
                    ->form([
                        Select::make('balance_type')
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


                // Payment Date Range Filter
                Filter::make('payment_date')
                    ->form([
                        DatePicker::make('paid_from')
                            ->native(false)
                            ->label('Paid From'),
                        DatePicker::make('paid_until')
                            ->native(false)
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

            ])
            ->actions([
                ActionGroup::make([

                    Action::make('recordBalance')
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
                                        ->options(fn() => PaymentMethod::where('school_id', Filament::getTenant()->id)
                                            ->where('active', true)
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
                    Action::make('viewReceipt')
                        ->icon('heroicon-o-document-text')
                        ->label('View Receipt')
                        ->url(fn(Payment $record) => PaymentResource::getUrl('view', ['record' => $record]))
                        ->openUrlInNewTab(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->emptyStateHeading('No payments found')
            ->emptyStateDescription('No payment history found for this student.')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->striped()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->defaultPaginationPageOption(25);
    }
}
