<?php

namespace App\Filament\Sms\Resources\InventoryResource\Pages;

use Filament\Actions;
use App\Models\PaymentType;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Sms\Resources\InventoryResource;

class CreateInventory extends CreateRecord
{
    protected static string $resource = InventoryResource::class;

    // protected function handleRecordCreation(array $data): Model
    // {

    //     return DB::transaction(function () use ($data) {
    //         // First create the payment type
    //         $paymentType = PaymentType::create([
    //             'school_id' => Filament::getTenant()->id,
    //             'name' => $data['name'],
    //             'category' => 'physical_item',
    //             'amount' => $data['selling_price'], // Use selling price as the amount
    //             'description' => $data['description'] ?? null,
    //             'active' => $data['is_active'] ?? true,
    //         ]);

    //         // Create the inventory item
    //         $inventory = static::getModel()::create([
    //             'school_id' => Filament::getTenant()->id,
    //             'payment_type_id' => $paymentType->id,
    //             'name' => $data['name'],
    //             'code' => $data['code'] ?? null,
    //             'description' => $data['description'] ?? null,
    //             'quantity' => $data['quantity'] ?? 0,
    //             'unit_price' => $data['unit_price'],
    //             'selling_price' => $data['selling_price'],
    //             'reorder_level' => $data['reorder_level'] ?? 10,
    //             'is_active' => $data['is_active'] ?? true,
    //         ]);

    //         // Create initial stock transaction if quantity > 0
    //         if ($inventory->quantity > 0) {
    //             $inventory->transactions()->create([
    //                 'school_id' => Filament::getTenant()->id,
    //                 'type' => 'IN',
    //                 'quantity' => $inventory->quantity,
    //                 'note' => 'Initial stock',
    //                 'created_by' => auth()->id(),
    //             ]);
    //         }

    //         return $inventory;
    //     });
    // }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            try {
                // First create the inventory item without payment_type_id
                $inventory = static::getModel()::create([
                    'school_id' => Filament::getTenant()->id,
                    'name' => $data['name'],
                    'code' => $data['code'] ?? null,
                    'description' => $data['description'] ?? null,
                    'quantity' => $data['quantity'] ?? 0,
                    'unit_price' => $data['unit_price'],
                    'selling_price' => $data['selling_price'],
                    'reorder_level' => $data['reorder_level'] ?? 10,
                    'is_active' => $data['is_active'] ?? true,
                ]);

                // Then create the payment type
                $paymentType = PaymentType::create([
                    'school_id' => Filament::getTenant()->id,
                    'name' => $data['name'],
                    'category' => 'physical_item',
                    'amount' => $data['selling_price'],
                    'description' => $data['description'] ?? null,
                    'active' => $data['is_active'] ?? true,
                ]);

                // Update the inventory with payment_type_id
                $inventory->update(['payment_type_id' => $paymentType->id]);

                // Create initial stock transaction if quantity > 0
                if ($inventory->quantity > 0) {
                    $inventory->transactions()->create([
                        'school_id' => Filament::getTenant()->id,
                        'type' => 'IN',
                        'quantity' => $inventory->quantity,
                        'note' => 'Initial stock',
                        'created_by' => auth()->id(),
                    ]);
                }

                return $inventory;
            } catch (\Exception $e) {
                Log::error('Error creating inventory:', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'data' => $data
                ]);
                throw $e;
            }
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Inventory created')
            ->body('New inventory item and payment type have been created successfully.');
    }
}
