<?php

namespace App\Filament\Sms\Resources\PaymentResource\Pages;

use App\Filament\Sms\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;
}
