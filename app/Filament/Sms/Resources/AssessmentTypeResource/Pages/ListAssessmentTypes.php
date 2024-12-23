<?php

namespace App\Filament\Sms\Resources\AssessmentTypeResource\Pages;

use App\Filament\Sms\Resources\AssessmentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssessmentTypes extends ListRecords
{
    protected static string $resource = AssessmentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
