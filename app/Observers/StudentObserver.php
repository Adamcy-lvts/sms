<?php

namespace App\Observers;

use App\Models\Student;
use App\Filament\Sms\Resources\ClassRoomResource\Widgets\ClassRoomStatsOverview;

class StudentObserver
{
    public function saved(Student $student)
    {
        if ($student->class_room_id) {
            ClassRoomStatsOverview::invalidateCache($student->class_room_id);
        }
    }

    public function deleted(Student $student)
    {
        if ($student->class_room_id) {
            ClassRoomStatsOverview::invalidateCache($student->class_room_id);
        }
    }
}
