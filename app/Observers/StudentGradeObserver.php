<?php

namespace App\Observers;

use App\Models\StudentGrade;
use App\Services\ReportCardService;
use App\Filament\Sms\Resources\ClassRoomResource\Widgets\ClassRoomStatsOverview;

class StudentGradeObserver
{
    protected $reportCardService;

    public function __construct(ReportCardService $reportCardService)
    {
        $this->reportCardService = $reportCardService;
    }

    public function saved(StudentGrade $grade)
    {
        // Invalidate cache when grade is updated
        $this->reportCardService->invalidateCache(
            $grade->student,
            $grade->assessment->term_id,
            $grade->assessment->academic_session_id
        );

        $classRoomId = $grade->student->class_room_id;
        ClassRoomStatsOverview::invalidateCache($classRoomId);
    }

    public function deleted(StudentGrade $grade)
    {
        $this->saved($grade);
    }
}