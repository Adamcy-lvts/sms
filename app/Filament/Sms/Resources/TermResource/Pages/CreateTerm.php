<?php

namespace App\Filament\Sms\Resources\TermResource\Pages;

use App\Filament\Sms\Resources\TermResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTerm extends CreateRecord
{
    protected static string $resource = TermResource::class;


    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
