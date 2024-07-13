<?php

namespace App\Filament\Sms\Resources\AcademicSessionResource\Pages;

use App\Filament\Sms\Resources\AcademicSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAcademicSessions extends ListRecords
{
    protected static string $resource = AcademicSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
