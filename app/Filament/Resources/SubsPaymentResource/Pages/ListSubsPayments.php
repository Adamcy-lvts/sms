<?php

namespace App\Filament\Resources\SubsPaymentResource\Pages;

use App\Filament\Resources\SubsPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubsPayments extends ListRecords
{
    protected static string $resource = SubsPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
