<?php

namespace App\Filament\Sms\Resources\StudentGradeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Sms\Pages\BulkGradeStudents;
use App\Filament\Sms\Resources\StudentGradeResource;

class ListStudentGrades extends ListRecords
{
    protected static string $resource = StudentGradeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('bulkGrade')
                ->label('Grade Students')
                ->url(BulkGradeStudents::getUrl())
                ->icon('heroicon-o-check-circle')
                ->color('primary'),
        ];
    }
}
