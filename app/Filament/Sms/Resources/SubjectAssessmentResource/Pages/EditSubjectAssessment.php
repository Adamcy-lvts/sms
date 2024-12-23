<?php

namespace App\Filament\Sms\Resources\SubjectAssessmentResource\Pages;

use App\Filament\Sms\Resources\SubjectAssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubjectAssessment extends EditRecord
{
    protected static string $resource = SubjectAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
