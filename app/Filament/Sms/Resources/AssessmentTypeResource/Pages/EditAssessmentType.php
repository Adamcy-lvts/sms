<?php

namespace App\Filament\Sms\Resources\AssessmentTypeResource\Pages;

use App\Filament\Sms\Resources\AssessmentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssessmentType extends EditRecord
{
    protected static string $resource = AssessmentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
