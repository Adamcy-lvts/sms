<?php

namespace App\Filament\Sms\Resources\PaymentResource\Pages;

use App\Models\Term;
use Filament\Actions;
use App\Models\Status;
use App\Models\Payment;
use App\Models\Student;
use App\Models\ClassRoom;
use App\Models\PaymentPlan;
use App\Models\PaymentType;
use Filament\Facades\Filament;
use App\Models\AcademicSession;
use App\Models\StudentPaymentPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use App\Filament\Sms\Resources\PaymentResource;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function beforeCreate(): void
    {
        $tenant = Filament::getTenant();
        $data = $this->data;


        // Validate payment plan amounts if this is a tuition payment
        if (in_array($data['payment_category'], ['tuition', 'combined'])) {
            $classRoom = ClassRoom::find($data['class_room_id']);
            $classLevel = $classRoom->getLevel();
            $paymentPlanType = $data['payment_plan_type'] ?? 'session';

            // Validate tuition payment amounts
            foreach ($data['payment_items'] as $item) {
                $paymentType = PaymentType::find($item['payment_type_id']);
                if ($paymentType && $paymentType->is_tuition) {
                    $expectedAmount = $paymentType->getAmountForClass($classLevel, $paymentPlanType);

                    if ($expectedAmount !== floatval($item['item_amount'])) {
                        Notification::make()
                            ->danger()
                            ->title('Invalid Payment Amount')
                            ->body("Amount for {$paymentType->name} does not match the payment plan amount.")
                            ->persistent()
                            ->send();

                        $this->halt();
                    }
                }
            }
        }

        $studentId = $data['student_id'];
        $academicSessionId = $data['academic_session_id'];
        $termId = $data['term_id'];
        $paymentTypeIds = array_column($data['payment_items'], 'payment_type_id');

        // Get student information with tenant check
        $student = Student::where('school_id', $tenant->id)
            ->with(['classRoom', 'admission'])
            ->find($studentId);

        // Get academic session and term info
        $academicSession = AcademicSession::find($academicSessionId);
        $term = Term::find($termId);

        // Start checking for existing payments
        $existingPayments = Payment::query()
            ->where('school_id', $tenant->id)
            ->where('student_id', $studentId)
            ->where('academic_session_id', $academicSessionId)
            ->where('term_id', $termId)
            // Find only fully paid payments
            ->whereHas('status', function ($query) {
                $query->where('name', 'paid');
            })
            // Look for payment items that match our current payment types
            ->whereHas('paymentItems', function ($query) use ($paymentTypeIds) {
                $query->whereIn('payment_type_id', $paymentTypeIds)
                    ->where(function ($q) {
                        // Consider an item paid if balance is 0 or deposit equals full amount
                        $q->where('balance', 0)
                            ->orWhere('deposit', DB::raw('amount'));
                    });
            })
            // Eager load relationships to avoid N+1 query problem
            ->with(['paymentItems.paymentType'])
            ->get();

        // If we found any existing payments
        if ($existingPayments->isNotEmpty()) {
            // Create an empty collection to store all duplicate items
            $duplicateItems = collect();

            // Loop through each existing payment
            foreach ($existingPayments as $payment) {
                // Filter payment items to only those that match our current payment types
                $items = $payment->paymentItems
                    ->whereIn('payment_type_id', $paymentTypeIds)
                    // Transform each item into a simpler array structure
                    ->map(function ($item) use ($payment) {
                        return [
                            'name' => $item->paymentType->name,
                            'amount' => $item->amount,
                            'reference' => $payment->reference,
                            'paid_at' => $payment->paid_at,
                        ];
                    });
                // Add these items to our collection
                $duplicateItems = $duplicateItems->concat($items);
            }

            // Group items by name and take first occurrence to avoid duplicates
            // For example, if "Library Fee" appears multiple times, we only show it once
            $groupedItems = $duplicateItems->groupBy('name')->map->first();

            // Create a formatted list of items with their amounts
            // Example: "Library Fees - ₦5,000.00, Exam Fees - ₦10,000.00"
            $itemsList = $groupedItems
                ->map(fn($item) => "{$item['name']} - ₦" . number_format($item['amount'], 2))
                ->join($groupedItems->count() > 1 ? ',<br>' : '');

            // Format payment references and dates for each payment
            // Example:
            // Reference: PAY-123
            // Paid On: 10 Nov, 2024
            $paymentDetails = $existingPayments->map(function ($payment) {
                return sprintf(
                    "Reference: %s\nPaid On: %s",
                    $payment->reference,
                    $payment->paid_at->format('j M, Y')
                );
            })->join("\n\n"); // Add double line break between multiple payments

            // Build the complete message using sprintf
            // %s are placeholders that get replaced with actual values in order
            $message = sprintf(
                "%s (%s) has already made payment for the following items for %s - %s:\n\n%s\n\nPayment Details:\n%s",
                $student->full_name,           // First %s - Student name
                $student->classRoom->name,     // Second %s - Class name
                $academicSession->name,        // Third %s - Session name
                $term->name,                   // Fourth %s - Term name
                $itemsList,                    // Fifth %s - List of items
                $paymentDetails                // Sixth %s - Payment details
            );

            // Show the error notification
            Notification::make()
                ->danger()                     // Red color for error
                ->title('Duplicate Payment Detected')
                ->body($message)
                // Add action buttons
                ->actions([
                    // Button to view payment history
                    Action::make('view_payment_details')
                        ->label('View Payment History')
                        ->url(PaymentResource::getUrl('index', [
                            'tenant' => $tenant,
                            // Pre-filter the payment list to show relevant payments
                            'tableFilters' => [
                                'student_id' => $studentId,
                                'academic_session_id' => $academicSessionId,
                                'term_id' => $termId,
                            ]
                        ]))
                        ->button(),

                    // Button to clear the form
                    Action::make('clear_form')
                        ->label('Clear Form')
                        ->color('gray')
                        ->action(function () {
                            $this->form->fill([]);
                        }),
                ])
                ->persistent()    // Notification won't auto-dismiss
                ->send();

            // Stop the form submission
            $this->halt();
        }

        // Verify payment types belong to tenant
        $validPaymentTypeIds = PaymentType::where('school_id', $tenant->id)
            ->whereIn('id', $paymentTypeIds)
            ->pluck('id')
            ->toArray();

        if (count($validPaymentTypeIds) !== count($paymentTypeIds)) {
            Notification::make()
                ->danger()
                ->title('Invalid Payment Types')
                ->body('One or more selected payment types do not belong to this school.')
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        $tenant = Filament::getTenant();

        return DB::transaction(function () use ($data, $tenant) {

            // Create payment plan if this is a tuition payment
            if (in_array($data['payment_category'], ['tuition', 'combined'])) {
                $tuitionItems = collect($data['payment_items'])
                    ->filter(function ($item) {
                        return PaymentType::find($item['payment_type_id'])->is_tuition;
                    });

                if ($tuitionItems->isNotEmpty()) {
                    // Get first tuition payment type to determine the plan
                    $tuitionType = PaymentType::find($tuitionItems->first()['payment_type_id']);
                    $classRoom = ClassRoom::find($data['class_room_id']);
                    $classLevel = $classRoom->getLevel();

                    // Find the payment plan
                    $paymentPlan = PaymentPlan::where([
                        'payment_type_id' => $tuitionType->id,
                        'class_level' => $classLevel,
                    ])->first();

                    if ($paymentPlan) {
                        // Create or update student payment plan
                        StudentPaymentPlan::updateOrCreate(
                            [
                                'student_id' => $data['student_id'],
                                'academic_session_id' => $data['academic_session_id'],
                            ],
                            [
                                'school_id' => $tenant->id,
                                'payment_plan_id' => $paymentPlan->id,
                                'created_by' => auth()->id(),
                                'notes' => "Plan selected during payment {$data['reference']}"
                            ]
                        );
                    }
                }
            }

            $paymentItems = collect($data['payment_items'] ?? []);
            $totalAmount = $paymentItems->sum('item_amount');
            $totalDeposit = $paymentItems->sum('item_deposit');
            $totalBalance = $paymentItems->sum('item_balance');

            // Clean up data
            unset($data['payment_items']);
            unset($data['payment_type_ids']);
            unset($data['enable_partial_payment']);
            unset($data['total_amount']);
            unset($data['total_deposit']);
            unset($data['total_balance']);

            // Get student with tenant check
            $student = Student::where('school_id', $tenant->id)
                ->findOrFail($data['student_id']);

            // Create the main payment record
            $payment = Payment::create([
                'school_id' => $tenant->id,
                'student_id' => $student->id,
                'receiver_id' => auth()->id(),
                'class_room_id' => $student->class_room_id,
                'payment_method_id' => $data['payment_method_id'],
                'academic_session_id' => $data['academic_session_id'],
                'term_id' => $data['term_id'],
                'status_id' => $this->determineStatus($totalDeposit, $totalAmount),
                'reference' => $data['reference'],
                'payer_name' => $data['payer_name'],
                'payer_phone_number' => $data['payer_phone_number'],
                'amount' => $totalAmount,
                'deposit' => $totalDeposit,
                'balance' => $totalBalance,
                'is_tuition' => in_array($data['payment_category'], ['tuition', 'combined']),
                'payment_plan_type' => $data['payment_plan_type'] ?? null,
                'payment_category' => $data['payment_category'] ?? null,
                'remark' => $data['remark'],
                'due_date' => $data['due_date'] ?? null,
                'paid_at' => $data['paid_at'],
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // Create payment items and handle inventory
            foreach ($paymentItems as $item) {
                // Load payment type with inventory
                $paymentType = PaymentType::with('inventory')
                    ->find($item['payment_type_id']);

                // Create payment item with quantity if it's a physical item
                $paymentItem = $payment->paymentItems()->create([
                    'payment_type_id' => $item['payment_type_id'],
                    'amount' => floatval($item['item_amount']),
                    'deposit' => floatval($item['item_deposit']),
                    'balance' => floatval($item['item_balance']),
                    'quantity' => $item['has_quantity'] ? $item['quantity'] : null,
                    'unit_price' => $item['has_quantity'] ? $item['unit_price'] : null,
                    'is_tuition' => $paymentType->is_tuition ?? false,
                ]);

                // Handle inventory reduction for physical items
                if (
                    $paymentType &&
                    $paymentType->category === 'physical_item' &&
                    $paymentType->inventory &&
                    isset($item['quantity'])
                ) {

                    // Validate stock availability
                    if ($paymentType->inventory->quantity < $item['quantity']) {
                        // let's return a noficiation for the user
                        Notification::make()
                            ->danger()
                            ->title('Insufficient Stock')
                            ->body("Insufficient stock for {$paymentType->name}")
                            ->persistent()
                            ->send();
                        // throw new \Exception("Insufficient stock for {$paymentType->name}");
                    }

                    // Create inventory transaction
                    $paymentType->inventory->transactions()->create([
                        'school_id' => $tenant->id,
                        'type' => 'OUT',
                        'quantity' => $item['quantity'],
                        'reference_type' => 'payment',
                        'reference_id' => $payment->id,
                        'note' => "Sold to {$student->full_name}",
                        'created_by' => auth()->id(),
                    ]);

                    // Update inventory quantity
                    $paymentType->inventory->decrement('quantity', $item['quantity']);
                }
            }

            // Show success notification with payment details
            Notification::make()
                ->success()
                ->title('Payment Recorded Successfully')
                ->body("Payment of ₦" . number_format($totalDeposit, 2) . " has been recorded for {$student->full_name}")
                ->actions([
                    Action::make('view_receipt')
                        ->label('View Receipt')
                        ->url(fn() => PaymentResource::getUrl('view', [
                            'tenant' => $tenant,
                            'record' => $payment
                        ]))
                        ->button(),
                ])
                ->persistent()
                ->send();

            return $payment;
        });
    }
    private function determineStatus($deposit, $amount): int
    {
        $statusName = match (true) {
            $deposit >= $amount => 'paid',
            $deposit > 0 => 'partial',
            default => 'pending'
        };

        return Status::where('type', 'payment')
            ->where('name', $statusName)
            ->first()?->id;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', ['tenant' => Filament::getTenant()]);
    }

    protected function onValidationError(ValidationException $exception): void
    {
        Notification::make()
            ->danger()
            ->title('Error')
            ->body($exception->getMessage())
            ->persistent()
            ->send();
    }
}
