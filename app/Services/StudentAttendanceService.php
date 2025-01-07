<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Term;
use App\Models\Student;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSummary;
use Illuminate\Support\Collection;

class StudentAttendanceService
{
    protected $calendarService;

    public function __construct(CalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    public function getAttendanceStats(Student $student, Term $term): array
    {
        $summary = AttendanceSummary::where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->firstOrFail();

        return [
            'current_stats' => $this->getCurrentStats($summary),
            'trends' => $this->getTrends($summary),
            'comparisons' => $this->getComparisons($summary),
            'charts_data' => $this->getChartsData($summary)
        ];
    }

    protected function getCurrentStats(AttendanceSummary $summary): array
    {
        return [
            'rate' => $summary->attendance_rate,
            'total_days' => $summary->total_days,
            'present_count' => $summary->present_count,
            'late_count' => $summary->late_count,
            'absent_count' => $summary->absent_count,
            'excused_count' => $summary->excused_count
        ];
    }

    protected function getTrends(AttendanceSummary $summary): array
    {
        return [
            'weekly' => $summary->weekly_stats ?? [],
            'monthly' => $summary->monthly_stats ?? [],
            'day_of_week' => $summary->day_of_week_stats ?? []
        ];
    }

    protected function getComparisons(AttendanceSummary $summary): array
    {
        $classAverage = AttendanceSummary::where('class_room_id', $summary->class_room_id)
            ->where('term_id', $summary->term_id)
            ->avg('attendance_rate');

        $previousTerms = AttendanceSummary::where('student_id', $summary->student_id)
            ->where('term_id', '<', $summary->term_id)
            ->orderBy('term_id', 'desc')
            ->limit(3)
            ->get(['term_id', 'attendance_rate']);

        return [
            'class_average' => $classAverage,
            'previous_terms' => $previousTerms->map(function ($term) {
                return [
                    'term_id' => $term->term_id,
                    'rate' => $term->attendance_rate
                ];
            })
        ];
    }

    protected function getChartsData(AttendanceSummary $summary): array
    {
        return [
            'daily' => $summary->daily_stats ?? [],
            'weekly' => $summary->weekly_stats ?? [],
            'monthly' => $summary->monthly_stats ?? []
        ];
    }
}