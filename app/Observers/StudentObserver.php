<?php

namespace App\Observers;

use App\Models\Student;
use App\Services\LimitService;
use Illuminate\Support\Facades\Storage;
use App\Filament\Sms\Resources\ClassRoomResource\Widgets\ClassRoomStatsOverview;

class StudentObserver
{
    // public function creating(Student $student)
    // {
    //     app(LimitService::class)->check(
    //         $student->school,
    //         'student_management',
    //         'max_students'
    //     );
    // }
    
    public function saved(Student $student)
    {
        if ($student->class_room_id) {
            ClassRoomStatsOverview::invalidateCache($student->class_room_id);
        }
    }

    public function deleted(Student $student): void
    {
        if ($student->class_room_id) {
            ClassRoomStatsOverview::invalidateCache($student->class_room_id);
        }

        if ($student->profile_picture) {
            Storage::disk('public')->delete($student->profile_picture);
        }
    }

    public function updating(Student $student): void
    {
        // Check if profile picture is being changed
        if ($student->isDirty('profile_picture') && $student->getOriginal('profile_picture')) {
            Storage::disk('public')->delete($student->getOriginal('profile_picture'));
        }
    }
}