<?php

namespace App\Filament\Resources\SubsPaymentResource\Pages;

use App\Models\Agent;
use Filament\Actions;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\EditRecord;
use Unicodeveloper\Paystack\Facades\Paystack;
use App\Filament\Resources\SubsPaymentResource;

class EditSubsPayment extends EditRecord
{
    protected static string $resource = SubsPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    
}
