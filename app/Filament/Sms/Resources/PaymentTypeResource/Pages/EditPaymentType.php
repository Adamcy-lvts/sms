<?php

namespace App\Filament\Sms\Resources\PaymentTypeResource\Pages;

use Filament\Actions;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Sms\Resources\PaymentTypeResource;

class EditPaymentType extends EditRecord
{
    protected static string $resource = PaymentTypeResource::class;

    // Fill form with inventory data if it exists
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Get the payment type with its inventory
        $paymentType = $this->record->load('inventory');
        
        // If this is a physical item with inventory
        if ($paymentType->category === 'physical_item' && $paymentType->inventory) {
            $data['unit_price'] = $paymentType->inventory->unit_price;
            $data['selling_price'] = $paymentType->inventory->selling_price;
            $data['initial_stock'] = $paymentType->inventory->quantity;
            $data['reorder_level'] = $paymentType->inventory->reorder_level;
        }

        return $data;
    }

    // Handle the update of both payment type and inventory

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        DB::transaction(function () use ($data) {
            // Set amount based on category
            $amount = null;
            if ($data['category'] === 'service_fee') {
                $amount = $data['amount'];
            } else if ($data['category'] === 'physical_item') {
                // For physical items, use selling_price as amount
                $amount = $data['selling_price'];
            }

            // Update payment type
            $this->record->update([
                'name' => $data['name'],
                'category' => $data['category'],
                'amount' => $amount,
                'description' => $data['description'] ?? null,
                'active' => $data['active'] ?? true,
            ]);

            // Handle inventory
            if ($data['category'] === 'physical_item') {
                // Update or create inventory
                $this->record->inventory()->updateOrCreate(
                    ['payment_type_id' => $this->record->id],
                    [
                        'school_id' => Filament::getTenant()->id,
                        'name' => $data['name'],
                        'quantity' => $data['initial_stock'] ?? 0,
                        'unit_price' => $data['unit_price'],
                        'selling_price' => $data['selling_price'],
                        'reorder_level' => $data['reorder_level'] ?? 10,
                        'is_active' => $data['active'] ?? true,
                    ]
                );
            } else {
                // If changing from physical to service, delete inventory
                if ($this->record->inventory) {
                    $this->record->inventory->delete();
                }
            }
        });

        return $this->record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function () {
                    // Delete associated inventory before deleting payment type
                    if ($this->record->inventory) {
                        $this->record->inventory->delete();
                    }
                }),
        ];
    }

    // Optional: Add success notification
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
