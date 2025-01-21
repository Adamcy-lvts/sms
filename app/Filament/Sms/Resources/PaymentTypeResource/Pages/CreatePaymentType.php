<?php

namespace App\Filament\Sms\Resources\PaymentTypeResource\Pages;

use Filament\Actions;
use App\Models\PaymentType;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Sms\Resources\PaymentTypeResource;

class CreatePaymentType extends CreateRecord
{
    protected static string $resource = PaymentTypeResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // Set amount based on category
            $amount = null;
            if ($data['category'] === 'service_fee') {
                $amount = $data['amount'];
            } else if ($data['category'] === 'physical_item') {
                // For physical items, use selling_price as amount
                $amount = $data['selling_price'];
            }

            // Create payment type
            $paymentType = PaymentType::create([
                'school_id' => Filament::getTenant()->id,
                'name' => $data['name'],
                'category' => $data['category'],
                'amount' => $amount, // Set amount according to category
                'description' => $data['description'] ?? null,
                'active' => $data['active'] ?? true,
            ]);

            // Create inventory for physical items
            if ($data['category'] === 'physical_item') {
                $paymentType->inventory()->create([
                    'school_id' => Filament::getTenant()->id,
                    'name' => $data['name'],
                    'quantity' => $data['initial_stock'] ?? 0,
                    'unit_price' => $data['unit_price'],
                    'selling_price' => $data['selling_price'],
                    'reorder_level' => $data['reorder_level'] ?? 10,
                    'is_active' => $data['active'] ?? true,
                ]);
            }

            return $paymentType;
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
