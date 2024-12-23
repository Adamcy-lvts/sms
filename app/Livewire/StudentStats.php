<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Term;
use App\Models\Student;
use App\Models\ClassRoom;
use App\Models\StudentGrade;
use Filament\Facades\Filament;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSummary;
use App\Models\SubjectAssessment;
use App\Services\CalendarService;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class StudentStats extends BaseWidget
{
    public ?Student $student = null;
    protected CalendarService $calendarService;

    // Constructor to accept student
    public function mount(?Student $student = null)
    {
        $this->student = $student ?? Filament::getTenant();
    }

    public function boot(CalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    protected function getStats(): array
    {
        return [
            // Attendance Rate 
            $this->getAttendanceStat(),
            // Academic Ranking
            $this->getCurrentRankingStat(),
            // Best Subject Performance
            $this->getBestSubjectStat(),
            // Assignment Completion
            $this->getAssignmentCompletionStat(),
        ];
    }


    // protected function getAttendanceStat(): Stat
    // {
    //     $currentTerm = Term::find(config('app.current_term')->id);
    //     $termStart = Carbon::parse($currentTerm->start_date);
    //     $termEnd = Carbon::parse($currentTerm->end_date);
    //     $today = Carbon::today();

    //     // Get school days calculation
    //     $schoolDays = $this->calendarService->getSchoolDays($this->student->school, $currentTerm);
    //     $totalSchoolDays = $schoolDays['total_days'];

    //     // Calculate days elapsed (excluding weekends and holidays)
    //     $elapsedSchoolDays = 0;
    //     $current = $termStart->copy();
    //     while ($current <= min($today, $termEnd)) {
    //         if (!$current->isWeekend() && !in_array($current->format('Y-m-d'), $schoolDays['excluded_dates'])) {
    //             $elapsedSchoolDays++;
    //         }
    //         $current->addDay();
    //     }
    //     // Get attendance summaries
    //     $attendanceSummary = AttendanceSummary::where('student_id', $this->student->id)
    //         ->where('term_id', $currentTerm->id)
    //         ->first();

    //     if (!$attendanceSummary) {
    //         return Stat::make('Attendance Rate', 'No Data')
    //             ->description('No attendance records yet')
    //             ->color('gray');
    //     }

    //     // Calculate term progress percentage
    //     $termProgress = ($elapsedSchoolDays / $totalSchoolDays) * 100;

    //     // Only show rating if term is at least 25% complete
    //     if ($termProgress < 25) {
    //         return Stat::make('Attendance Rate', 'Term Started')
    //             ->description('Too early to calculate')
    //             ->color('info');
    //     }

    //     $currentRate = (float) $attendanceSummary->attendance_percentage;
    //     $daysPresent = $attendanceSummary->present_count;

    //     // Calculate attendance based on elapsed days instead of total term days
    //     $adjustedRate = ($daysPresent / $elapsedSchoolDays) * 100;

    //     $description = match (true) {
    //         $termProgress >= 90 => 'Term nearly complete',
    //         $termProgress >= 50 => 'Mid-term progress',
    //         default => 'Term in progress'
    //     };

    //     $color = match (true) {
    //         $adjustedRate >= 90 => 'success',
    //         $adjustedRate >= 75 => 'info',
    //         $adjustedRate >= 60 => 'warning',
    //         default => 'danger'
    //     };

    //     return Stat::make('Attendance Rate', number_format($adjustedRate, 1) . '%')
    //         ->description($description)
    //         ->descriptionIcon('heroicon-m-calendar')
    //         ->color($color)
    //         ->chart([$adjustedRate]);
    // }



    protected function getAttendanceStat(): Stat
    {
        // Keep current term calculations
        $currentTermStats = $this->getCurrentTermStats();
        if (!$currentTermStats['hasData']) {
            return $currentTermStats['stat'];
        }

        // Add historical context
        $historicalStats = $this->getHistoricalStats();

        return Stat::make('Attendance Rate', number_format($currentTermStats['adjustedRate'], 1) . '%')
            ->description($this->getAttendanceDescription($currentTermStats, $historicalStats))
            ->descriptionIcon('heroicon-m-calendar')
            ->color($currentTermStats['color'])
            // Show trend with historical data
            ->chart(array_merge(
                $historicalStats['rates'],
                [$currentTermStats['adjustedRate']]
            ));
    }

    private function getHistoricalStats(): array
    {
        // Get last 3 terms' attendance
        return AttendanceSummary::where('student_id', $this->student->id)
            ->whereNot('term_id', config('app.current_term')->id)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->map(fn($summary) => $summary->attendance_percentage)
            ->toArray();
    }


    // protected function getCurrentRankingStat(): Stat
    // {
    //     // Get current class grades
    //     $currentClassGrades = StudentGrade::whereHas('student', function ($query) {
    //         $query->where('class_room_id', $this->student->class_room_id);
    //     })
    //         ->whereHas('assessment', function ($query) {
    //             $query->where([
    //                 'academic_session_id' => config('app.current_session')->id,
    //                 'term_id' => config('app.current_term')->id
    //             ]);
    //         })
    //         ->get();

    //     if ($currentClassGrades->isEmpty()) {
    //         return Stat::make('Current Ranking', 'No Data')
    //             ->description('No grades recorded yet')
    //             ->color('gray');
    //     }

    //     // Calculate averages and rank
    //     $averages = $currentClassGrades
    //         ->groupBy('student_id')
    //         ->map(fn($grades) => $grades->avg('score'))
    //         ->sort()
    //         ->reverse();

    //     $totalStudents = $averages->count();
    //     $rank = $averages->keys()->search($this->student->id) + 1;
    //     $percentile = (($totalStudents - $rank + 1) / $totalStudents) * 100;

    //     // Get previous ranking if available
    //     $previousRank = $this->getPreviousRanking();
    //     $rankChange = $previousRank ? $previousRank - $rank : null;

    //     return Stat::make('Class Ranking', $this->getOrdinal($rank) . ' of ' . $totalStudents)
    //         ->description(
    //             $rankChange !== null
    //                 ? sprintf(
    //                     '%s %d position(s)',
    //                     $rankChange > 0 ? 'Improved' : 'Dropped',
    //                     abs($rankChange)
    //                 )
    //                 : "Top " . number_format($percentile, 0) . "% of class"
    //         )
    //         ->descriptionIcon($rankChange > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
    //         ->color($percentile >= 75 ? 'success' : ($percentile >= 50 ? 'warning' : 'danger'));
    // }

    protected function getCurrentRankingStat(): Stat
    {
        // Try to get average ranking from previous classes first
        $averageRanking = $this->getAverageHistoricalRanking();
        if ($averageRanking) {
            return $this->formatRankingStat($averageRanking, 'Historical Average');
        }

        // Fallback to current/previous term ranking in current class
        $currentRanking = $this->getCurrentTermRanking() ?? $this->getPreviousTermRanking();
        if ($currentRanking) {
            return $this->formatRankingStat($currentRanking, 'Current Class');
        }

        return Stat::make('Class Ranking', 'No Data')
            ->description('Insufficient grade data')
            ->color('gray');
    }

    protected function getAverageHistoricalRanking(): ?array
    {
        // Get all previous classes
        $previousClasses = ClassRoom::where('school_id', $this->student->school_id)
            ->where('id', '!=', $this->student->class_room_id)
            ->get();

        $rankings = collect();

        foreach ($previousClasses as $class) {
            $termRankings = StudentGrade::whereHas(
                'student',
                fn($q) =>
                $q->where('class_room_id', $class->id)
            )->get()
                ->groupBy('term_id')
                ->map(function ($grades) {
                    $averages = $grades->groupBy('student_id')
                        ->map(fn($g) => $g->avg('score'))
                        ->sort()
                        ->reverse();

                    $rank = $averages->keys()->search($this->student->id) + 1;
                    $total = $averages->count();

                    return [
                        'rank' => $rank,
                        'total' => $total,
                        'percentile' => (($total - $rank + 1) / $total) * 100
                    ];
                });

            $rankings = $rankings->merge($termRankings);
        }

        if ($rankings->isEmpty()) return null;

        return [
            'rank' => round($rankings->avg('rank')),
            'total' => round($rankings->avg('total')),
            'percentile' => $rankings->avg('percentile')
        ];
    }

    protected function getCurrentTermRanking(): ?array
    {
        return $this->getTermRanking(
            $this->student->class_room_id,
            config('app.current_session')->id,
            config('app.current_term')->id
        );
    }

    protected function getPreviousTermRanking(): ?array
    {
        $previousTerm = Term::where('school_id', $this->student->school_id)
            ->where('start_date', '<', config('app.current_term')->start_date)
            ->orderBy('start_date', 'desc')
            ->first();

        if (!$previousTerm) return null;

        return $this->getTermRanking(
            $this->student->class_room_id,
            config('app.current_session')->id,
            $previousTerm->id
        );
    }

    protected function getTermRanking($classId, $sessionId, $termId): ?array
    {
        $grades = StudentGrade::whereHas(
            'student',
            fn($q) =>
            $q->where('class_room_id', $classId)
        )->whereHas(
            'assessment',
            fn($q) =>
            $q->where([
                'academic_session_id' => $sessionId,
                'term_id' => $termId
            ])
        )->get();

        if ($grades->isEmpty()) return null;

        $averages = $grades->groupBy('student_id')
            ->map(fn($g) => $g->avg('score'))
            ->sort()
            ->reverse();

        $rank = $averages->keys()->search($this->student->id) + 1;
        $total = $averages->count();

        return [
            'rank' => $rank,
            'total' => $total,
            'percentile' => (($total - $rank + 1) / $total) * 100
        ];
    }

    protected function formatRankingStat(array $data, string $type): Stat
    {
        return Stat::make(
            'Class Ranking',
            $this->getOrdinal($data['rank']) . ' of ' . $data['total']
        )
            ->description($type . ' - Top ' . number_format($data['percentile'], 0) . '%')
            ->descriptionIcon('heroicon-m-academic-cap')
            ->color(
                $data['percentile'] >= 75 ? 'success' : ($data['percentile'] >= 50 ? 'warning' : 'danger')
            );
    }

    protected function getBestSubjectStat(): Stat
    {
        // Get student's subject performances
        $subjectPerformances = StudentGrade::where('student_id', $this->student->id)
            ->with(['assessment.subject'])
            ->get()
            ->groupBy('assessment.subject.name')
            ->map(function ($grades) {
                return [
                    'average' => $grades->avg('score'),
                    'trend' => $this->calculateSubjectTrend($grades)
                ];
            });

        if ($subjectPerformances->isEmpty()) {
            return Stat::make('Best Subject', 'No Data')
                ->description('No subject grades yet')
                ->color('gray');
        }

        // Find best subject
        $bestSubject = $subjectPerformances
            ->sortByDesc('average')
            ->first();

        $bestSubjectName = $subjectPerformances->keys()->first();

        return Stat::make('Best Subject', $bestSubjectName)
            ->description(sprintf('%.1f%% average', $bestSubject['average']))
            ->descriptionIcon(
                $bestSubject['trend'] > 0
                    ? 'heroicon-m-arrow-trending-up'
                    : 'heroicon-m-arrow-trending-down'
            )
            ->color('success');
    }

    protected function getAssignmentCompletionStat(): Stat
    {
        // Implementation depends on how assignments are tracked
        // This is a placeholder that you'll need to customize
        return Stat::make('Assignment Completion', 'Coming Soon')
            ->description('Feature in development')
            ->color('gray');
    }

    // Helper Methods

    protected function getPreviousRanking(): ?int
    {
        // Get previous term's ranking
        $previousTerm = Term::where('school_id', $this->student->school_id)
            ->where('start_date', '<', config('app.current_term')->start_date)
            ->orderBy('start_date', 'desc')
            ->first();

        if (!$previousTerm) return null;

        // Calculate previous ranking logic here
        // Similar to current ranking calculation but for previous term
        return null; // Placeholder
    }

    protected function calculateSubjectTrend($grades)
    {
        if ($grades->count() < 2) return 0;

        $chronological = $grades->sortBy('assessment.assessment_date');
        $first = $chronological->first()->score;
        $last = $chronological->last()->score;

        return $last - $first;
    }

    protected function getOrdinal($number)
    {
        $ends = array('th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');
        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
            return $number . 'th';
        } else {
            return $number . $ends[$number % 10];
        }
    }
}
