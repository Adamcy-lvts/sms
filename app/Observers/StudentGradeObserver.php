<?php

namespace App\Observers;

use App\Models\StudentGrade;
use App\Services\ReportCardService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Filament\Sms\Resources\ClassRoomResource\Widgets\ClassRoomStatsOverview;

class StudentGradeObserver
{
    protected $reportCardService;
    protected $cacheInvalidatingFields = [
        'score',
        'subject_id',
        'assessment_type_id',
        'class_room_id',
        'academic_session_id',
        'term_id',
    ];

    public function __construct(ReportCardService $reportCardService)
    {
        $this->reportCardService = $reportCardService;
    }

    public function saved(StudentGrade $grade)
    {
        try {
            if ($this->shouldInvalidateCache($grade)) {
                // Force clear all related caches
                Cache::tags([
                    'report-cards',
                    "student:{$grade->student_id}",
                    "class:{$grade->student->class_room_id}"
                ])->flush();

                $this->reportCardService->invalidateCache(
                    $grade->student,
                    $grade->term_id,
                    $grade->academic_session_id
                );

                // Invalidate classroom stats
                if ($grade->student?->class_room_id) {
                    ClassRoomStatsOverview::invalidateCache($grade->student->class_room_id);
                }

                // Log::info('Grade cache invalidated', [
                //     'grade_id' => $grade->id,
                //     'student_id' => $grade->student_id,
                //     'changes' => $grade->getDirty()
                // ]);
            }
        } catch (\Exception $e) {
            // Log error but also force cache clear
            Log::error('Error in StudentGradeObserver: ' . $e->getMessage(), [
                'grade_id' => $grade->id,
                'student_id' => $grade->student_id ?? null,
                'changes' => $grade->getDirty()
            ]);
            
            // Force clear cache as fallback
            Cache::tags(['report-cards'])->flush();
        }
    }

    protected function shouldInvalidateCache(StudentGrade $grade): bool
    {
        // Always invalidate for new records
        if ($grade->wasRecentlyCreated) {
            return true;
        }

        // Check if any cache-invalidating fields were modified
        return collect($this->cacheInvalidatingFields)
            ->some(fn($field) => $grade->isDirty($field));
    }

    protected function invalidateClassReports(StudentGrade $grade)
    {
        // Invalidate old class reports if class changed
        if ($grade->getOriginal('class_room_id')) {
            $this->reportCardService->invalidateCache(
                null,
                $grade->term_id,
                $grade->academic_session_id,
                $grade->getOriginal('class_room_id')
            );
        }

        // Invalidate new class reports
        if ($grade->class_room_id) {
            $this->reportCardService->invalidateCache(
                null,
                $grade->term_id,
                $grade->academic_session_id,
                $grade->class_room_id
            );
        }
    }

    public function deleted(StudentGrade $grade)
    {
        $this->saved($grade);
    }
}