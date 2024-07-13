<?php

namespace App\Filament\Sms\Resources\AcademicSessionResource\Pages;

use App\Filament\Sms\Resources\AcademicSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAcademicSession extends CreateRecord
{
    protected static string $resource = AcademicSessionResource::class;


    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    
}
