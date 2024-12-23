<?php

namespace App\Observers;

use App\Models\Teacher;
use App\Filament\Sms\Resources\ClassRoomResource\Widgets\TeachersTable;

class TeacherObserver
{
    public function saved(Teacher $teacher)
    {
        foreach ($teacher->classRooms as $classroom) {
            TeachersTable::invalidateCache($classroom->id);
        }
    }

    public function deleted(Teacher $teacher)
    {
        foreach ($teacher->classRooms as $classroom) {
            TeachersTable::invalidateCache($classroom->id);
        }
    }
}
