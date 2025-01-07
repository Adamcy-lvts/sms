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
            'payment_items' => $payment->paymentItems->map(fn($item) => [
                'payment_type_id' => $item->payment_type_id,
                'item_amount' => $item->amount,
                'item_deposit' => $item->deposit,
                'item_balance' => $item->balance,
            ])->toArray(),

            // Payment totals
            'total_amount' => $payment->amount,
            'total_deposit' => $payment->deposit,
            'total_balance' => $payment->balance,

            // Payer information
            'payer_name' => $payment->payer_name,
            'payer_phone_number' => $payment->payer_phone_number,

            // Dates and status
            'due_date' => $payment->due_date,
            'paid_at' => $payment->paid_at,
            'status_id' => $payment->status_id,

            // Other fields
            'reference' => $payment->reference,
            'remark' => $payment->remark,
            'enable_partial_payment' => $payment->balance > 0,
        ];

        // Add meta data handling
        $data['meta_data'] = [
            'terms' => $this->record->getTerms(),
            'show_terms' => $this->record->shouldShowTerms(),
        ];

        return $data;
    }

    protected function afterSave(): void
    {
        // Begin transaction
        DB::beginTransaction();

        try {
            // Update payment items
            $updatedItems = collect($this->data['payment_items'] ?? []);
            
            // Delete all old items first
            $this->record->paymentItems()->delete();

            // Create new items
            foreach ($updatedItems as $item) {
                $this->record->paymentItems()->create([
                    'payment_type_id' => $item['payment_type_id'],
                    'amount' => $item['item_amount'],
                    'deposit' => $item['item_deposit'],
                    'balance' => $item['item_balance'],
                ]);
            }

            // Update payment with new totals and who modified it
            $this->record->update([
                'amount' => $this->data['total_amount'],
                'deposit' => $this->data['total_deposit'],
                'balance' => $this->data['total_balance'],
                'updated_by' => auth()->id()
            ]);

            DB::commit();

            // Show success notification
            Notification::make()
                ->success()
                ->title('Payment Updated')
                ->send();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
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