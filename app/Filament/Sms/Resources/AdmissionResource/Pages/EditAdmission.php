<?php

namespace App\Filament\Sms\Resources\AdmissionResource\Pages;

use App\Filament\Sms\Resources\AdmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdmission extends EditRecord
{
    protected static string $resource = AdmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('index');
}
}
