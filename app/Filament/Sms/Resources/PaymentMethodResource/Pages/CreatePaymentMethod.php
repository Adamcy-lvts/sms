<?php

namespace App\Filament\Sms\Resources\PaymentMethodResource\Pages;

use App\Filament\Sms\Resources\PaymentMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentMethod extends CreateRecord
{
    protected static string $resource = PaymentMethodResource::class;
}
