<?php

namespace App\Filament\Sms\Resources\TemplateResource\Pages;

use App\Filament\Sms\Resources\TemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTemplate extends CreateRecord
{
    protected static string $resource = TemplateResource::class;


    protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('index');
}
}
