<?php

namespace App\Filament\Sms\Resources\AdmissionResource\Pages;

use App\Filament\Sms\Resources\AdmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdmissions extends ListRecords
{
    protected static string $resource = AdmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    
}
