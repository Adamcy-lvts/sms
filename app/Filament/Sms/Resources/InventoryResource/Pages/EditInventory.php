<?php

namespace App\Filament\Sms\Resources\InventoryResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Sms\Resources\InventoryResource;

class EditInventory extends EditRecord
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (Model $record) {
                    // Delete the associated payment type first
                    if ($record->paymentType) {
                        $record->paymentType->delete();
                    }
                }),
                
            Actions\Action::make('viewHistory')
                ->label('Stock History')
                ->icon('heroicon-o-clock')
                ->url(fn ($record) => "#")
                ->openUrlInNewTab(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $oldQuantity = $record->quantity;
            $newQuantity = $data['quantity'];

            // Update the payment type first
            if ($record->paymentType) {
                $record->paymentType->update([
                    'name' => $data['name'],
                    'amount' => $data['selling_price'], // Update amount to reflect new selling price
                    'description' => $data['description'] ?? null,
                    'active' => $data['is_active'] ?? true,
                ]);
            }

            // Update the inventory record
            $record->update($data);

            // If quantity changed, create a transaction record
            if ($oldQuantity !== $newQuantity) {
                $difference = $newQuantity - $oldQuantity;
                $type = $difference > 0 ? 'IN' : 'OUT';
                
                $record->transactions()->create([
                    'school_id' => $record->school_id,
                    'type' => $type,
                    'quantity' => abs($difference),
                    'note' => 'Stock adjusted through edit',
                    'created_by' => auth()->id(),
                ]);
            }

            return $record;
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Inventory updated')
            ->body('The inventory item and payment type have been updated successfully.');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load the payment type relationship if not loaded
        if (!$this->record->relationLoaded('paymentType')) {
            $this->record->load('paymentType');
        }
        
        return $data;
    }
}