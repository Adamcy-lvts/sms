<?php

namespace App\Filament\Sms\Resources\TeacherResource\Pages;

use App\Filament\Sms\Resources\TeacherResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTeacher extends ViewRecord
{
    protected static string $resource = TeacherResource::class;

    protected static string $view = 'filament.sms.resources.teacher-resource.teacher-profile';

    public $teacher;


    public function mount($record): void
    {
        $this->record = $this->resolveRecord($record);

        try {
            $this->teacher = $this->record;
        } catch (\Exception $e) {
            // Handle the exception, e.g., log it or set an error message
            return;
        }
    }
}
