<?php

namespace App\Filament\Sms\Resources\VariableTemplateResource\Pages;

use App\Filament\Sms\Resources\VariableTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateVariableTemplate extends CreateRecord
{
    protected static string $resource = VariableTemplateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
