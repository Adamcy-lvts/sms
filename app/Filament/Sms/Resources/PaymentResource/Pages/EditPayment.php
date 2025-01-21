<?php

namespace App\Filament\Sms\Resources\PaymentResource\Pages;

use Filament\Actions;
use App\Models\PaymentItem;
use App\Models\PaymentType;
use App\Models\PaymentHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Sms\Resources\PaymentResource;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load the payment with all necessary relationships
        $payment = $this->record->load([
            'paymentItems.paymentType',
            'student.classRoom',
            'student.admission',
            'academicSession',
            'term',
            'paymentMethod',
            'status'
        ]);

        // Fill form data
        $data = [
            // Student information
            'student_id' => $payment->student_id,
            'class_room_id' => $payment->student->class_room_id,

            // Academic information
            'academic_session_id' => $payment->academic_session_id,
            'term_id' => $payment->term_id,
            'payment_method_id' => $payment->payment_method_id,

            // Payment Types and Items
            'payment_type_ids' => $payment->paymentItems->pluck('payment_type_id')->toArray(),
            'payment_items' => $payment->paymentItems->map(function ($item) {
                $baseData = [
                    'payment_type_id' => $item->payment_type_id,
                    'item_amount' => $item->amount,
                    'item_deposit' => $item->deposit,
                    'item_balance' => $item->balance,
                ];

                // Add physical item fields if applicable
                if ($item->paymentType?->category === 'physical_item') {
                    $baseData['has_quantity'] = true;
                    $baseData['quantity'] = $item->quantity;
                    $baseData['original_quantity'] = $item->quantity; // Add this line to track original quantity
                    $baseData['unit_price'] = $item->unit_price;
                    $baseData['max_quantity'] = $item->paymentType->inventory?->quantity + ($item->quantity ?? 0);
                }

                return $baseData;
            })->toArray(),

            // Payment totals
            'total_amount' => $payment->amount,
            'total_deposit' => $payment->deposit,
            'total_balance' => $payment->balance,

            // Rest of your existing data...
            'payer_name' => $payment->payer_name,
            'payer_phone_number' => $payment->payer_phone_number,
            'due_date' => $payment->due_date,
            'paid_at' => $payment->paid_at,
            'status_id' => $payment->status_id,
            'reference' => $payment->reference,
            'remark' => $payment->remark,
            'enable_partial_payment' => $payment->balance > 0,
            'meta_data' => [
                'terms' => $payment->getTerms(),
                'show_terms' => $payment->shouldShowTerms(),
            ],
        ];

        return $data;
    }

    // protected function afterSave(): void
    // {
    //     DB::beginTransaction();

    //     try {
    //         $updatedItems = collect($this->data['payment_items'] ?? []);

    //         // Get original items before deletion
    //         $originalItems = $this->record->paymentItems()
    //             ->with('paymentType.inventory')
    //             ->get()
    //             ->keyBy('payment_type_id');

    //         // Delete all old items first
    //         $this->record->paymentItems()->delete();

    //         // Create new items
    //         foreach ($updatedItems as $item) {
    //             $paymentType = PaymentType::with('inventory')->find($item['payment_type_id']);

    //             // Create payment item
    //             $paymentItem = $this->record->paymentItems()->create([
    //                 'payment_type_id' => $item['payment_type_id'],
    //                 'amount' => $item['item_amount'],
    //                 'deposit' => $item['item_deposit'],
    //                 'balance' => $item['item_balance'],
    //                 'quantity' => isset($item['has_quantity']) && $item['has_quantity'] ? $item['quantity'] : null,
    //                 'unit_price' => isset($item['has_quantity']) && $item['has_quantity'] ? $item['unit_price'] : null,
    //             ]);

    //             // Handle inventory for physical items
    //             if (
    //                 $paymentType &&
    //                 $paymentType->category === 'physical_item' &&
    //                 $paymentType->inventory &&
    //                 isset($item['quantity'])
    //             ) {
    //                 $originalQuantity = $originalItems->get($item['payment_type_id'])?->quantity ?? 0;
    //                 $newQuantity = $item['quantity'];
    //                 $quantityDifference = $newQuantity - $originalQuantity;

    //                 // Only process inventory if there's an actual change in quantity
    //                 if ($quantityDifference !== 0) {
    //                     // Create inventory transaction
    //                     $paymentType->inventory->transactions()->create([
    //                         'school_id' => $this->record->school_id,
    //                         'type' => $quantityDifference > 0 ? 'OUT' : 'IN',
    //                         'quantity' => abs($quantityDifference),
    //                         'reference_type' => 'payment_update',
    //                         'reference_id' => $this->record->id,
    //                         'note' => "Updated quantity from {$originalQuantity} to {$newQuantity} for {$this->record->student->full_name}",
    //                         'created_by' => auth()->id(),
    //                     ]);

    //                     // Update inventory quantity
    //                     if ($quantityDifference > 0) {
    //                         $paymentType->inventory->decrement('quantity', $quantityDifference);
    //                     } else {
    //                         $paymentType->inventory->increment('quantity', abs($quantityDifference));
    //                     }
    //                 }
    //             }
    //         }

    //         // Update payment totals
    //         $this->record->update([
    //             'amount' => $this->data['total_amount'],
    //             'deposit' => $this->data['total_deposit'],
    //             'balance' => $this->data['total_balance'],
    //             'updated_by' => auth()->id()
    //         ]);

    //         DB::commit();

    //         Notification::make()
    //             ->success()
    //             ->title('Payment Updated')
    //             ->send();
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         throw $e;
    //     }
    // }

    protected function afterSave(): void
    {
        DB::transaction(function () {
            // Get original payment items before updating
            $originalItems = $this->record->paymentItems()
                ->with('paymentType.inventory')
                ->get()
                ->keyBy('payment_type_id');

            // Get updated items
            $updatedItems = collect($this->data['payment_items'] ?? []);

            // Delete all old items first
            $this->record->paymentItems()->delete();

            // Create new items and handle inventory
            foreach ($updatedItems as $item) {
                $paymentType = PaymentType::with('inventory')->find($item['payment_type_id']);

                // Create payment item
                $paymentItem = $this->record->paymentItems()->create([
                    'payment_type_id' => $item['payment_type_id'],
                    'amount' => $item['item_amount'],
                    'deposit' => $item['item_deposit'],
                    'balance' => $item['item_balance'],
                    'quantity' => isset($item['has_quantity']) && $item['has_quantity'] ? $item['quantity'] : null,
                    'unit_price' => isset($item['has_quantity']) && $item['has_quantity'] ? $item['unit_price'] : null,
                ]);

                // Handle inventory for physical items
                if ($paymentType && $paymentType->category === 'physical_item' && $paymentType->inventory) {
                    $originalQuantity = $originalItems->get($item['payment_type_id'])?->quantity ?? 0;
                    $newQuantity = $item['quantity'] ?? 0;
                    $quantityDifference = $newQuantity - $originalQuantity;

                    if ($quantityDifference != 0) {
                        // Create inventory transaction
                        $paymentType->inventory->transactions()->create([
                            'school_id' => $this->record->school_id,
                            'type' => $quantityDifference > 0 ? 'OUT' : 'IN',
                            'quantity' => abs($quantityDifference),
                            'reference_type' => 'payment_update',
                            'reference_id' => $this->record->id,
                            'note' => $quantityDifference > 0
                                ? "Additional items taken for {$this->record->student->full_name}"
                                : "Items returned for {$this->record->student->full_name}",
                            'created_by' => auth()->id(),
                        ]);

                        // Update inventory quantity
                        if ($quantityDifference > 0) {
                            // Taking more items
                            $paymentType->inventory->decrement('quantity', $quantityDifference);
                        } else {
                            // Returning items
                            $paymentType->inventory->increment('quantity', abs($quantityDifference));
                        }
                    }
                }
            }

            // Update payment totals
            $this->record->update([
                'amount' => $this->data['total_amount'],
                'deposit' => $this->data['total_deposit'],
                'balance' => $this->data['total_balance'],
                'updated_by' => auth()->id()
            ]);

            Notification::make()
                ->success()
                ->title('Payment Updated')
                ->body('Payment and inventory have been updated successfully.')
                ->send();
        });
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Ensure meta_data is properly structured
        $metaData = [
            'terms' => $data['meta_data']['terms'] ?? $record->getTerms(),
            'show_terms' => $data['meta_data']['show_terms'] ?? $record->shouldShowTerms(),
        ];
        $data['meta_data'] = $metaData;

        $record->update($data);
        return $record;
    }
}
