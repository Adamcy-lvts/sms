<?php

namespace App\Filament\Sms\Resources\PaymentTypeResource\Pages;

use App\Filament\Sms\Resources\PaymentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentType extends EditRecord
{
    protected static string $resource = PaymentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
