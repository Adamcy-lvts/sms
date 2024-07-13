<?php

namespace App\Filament\Sms\Resources\AcademicSessionResource\Pages;

use App\Filament\Sms\Resources\AcademicSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcademicSession extends EditRecord
{
    protected static string $resource = AcademicSessionResource::class;

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
