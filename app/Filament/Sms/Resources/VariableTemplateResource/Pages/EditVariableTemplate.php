<?php

namespace App\Filament\Sms\Resources\VariableTemplateResource\Pages;

use App\Filament\Sms\Resources\VariableTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVariableTemplate extends EditRecord
{
    protected static string $resource = VariableTemplateResource::class;

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
