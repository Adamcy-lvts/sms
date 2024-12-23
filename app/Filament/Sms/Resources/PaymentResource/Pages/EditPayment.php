<?php

namespace App\Filament\Sms\Resources\PaymentResource\Pages;

use Filament\Actions;
use App\Models\PaymentType;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Sms\Resources\PaymentResource;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['payment_type_ids'] = $this->record->paymentItems->pluck('payment_type_id')->toArray();
        return $data;
    }

    protected function afterSave(): void
    {
        // Get the new payment types
        $newPaymentTypeIds = collect($this->data['payment_type_ids'] ?? []);

        // Delete removed payment items
        $this->record->paymentItems()
            ->whereNotIn('payment_type_id', $newPaymentTypeIds)
            ->delete();

        // Add new payment items
        $newPaymentTypeIds->each(function ($paymentTypeId) {
            if (!$this->record->paymentItems()->where('payment_type_id', $paymentTypeId)->exists()) {
                $paymentType = PaymentType::find($paymentTypeId);
                if ($paymentType) {
                    $this->record->paymentItems()->create([
                        'payment_type_id' => $paymentTypeId,
                        'amount' => $paymentType->amount,
                        'deposit' => $paymentType->amount,
                        'balance' => 0,
                    ]);
                }
            }
        });
    }
}
