<?php

namespace App\Filament\Sms\Resources\PaymentTypeResource\Pages;

use App\Filament\Sms\Resources\PaymentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentType extends CreateRecord
{
    protected static string $resource = PaymentTypeResource::class;
}
