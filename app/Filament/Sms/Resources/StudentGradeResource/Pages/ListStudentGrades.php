<?php

namespace App\Filament\Sms\Resources\StudentGradeResource\Pages;

use App\Filament\Sms\Resources\StudentGradeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentGrades extends ListRecords
{
    protected static string $resource = StudentGradeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
