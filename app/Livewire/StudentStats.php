<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Term;
use App\Models\Student;
use App\Models\ClassRoom;
use App\Models\ReportCard;
use App\Models\StudentGrade;
use Filament\Facades\Filament;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSummary;
use App\Models\SubjectAssessment;
use App\Services\CalendarService;
use Illuminate\Support\Facades\DB;
use Illuminate\Container\Attributes\Log;
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
            // Worst Subject Performance
            $this->getWorstSubjectStat(),
            // Commented out Assignment Completion
            // $this->getAssignmentCompletionStat(),
        ];
    }


    protected function getAttendanceStat(): Stat
    {
        // Get current term information
        $currentTerm = Term::find(config('app.current_term')->id);
        $termStart = Carbon::parse($currentTerm->start_date);
        $termEnd = Carbon::parse($currentTerm->end_date);
        $today = Carbon::today();

        // Check if term is already completed
        $isTermCompleted = $termEnd->isPast();

        // Get school days calculation
        $schoolDays = $this->calendarService->getSchoolDays($this->student->school, $currentTerm);
        $totalSchoolDays = $schoolDays['total_days'];

        // Calculate elapsed school days
        $elapsedSchoolDays = 0;
        $current = $termStart->copy();
        while ($current <= min($today, $termEnd)) {
            if (!$current->isWeekend() && !in_array($current->format('Y-m-d'), $schoolDays['excluded_dates'])) {
                $elapsedSchoolDays++;
            }
            $current->addDay();
        }

        // Get attendance summaries
        $attendanceSummary = AttendanceSummary::where('student_id', $this->student->id)
            ->where('term_id', $currentTerm->id)
            ->first();

        if (!$attendanceSummary) {
            return Stat::make('Attendance Rate', 'No Data')
                ->description('No attendance records available')
                ->color('gray');
        }

        // Calculate term progress percentage
        $termProgress = ($elapsedSchoolDays / $totalSchoolDays) * 100;

        if ($termProgress < 25) {
            return Stat::make('Attendance Rate', 'Term Started')
                ->description('Begining of term - attendance tracking in progress')
                ->color('info');
        }

        // Include both present AND late attendance
        $daysPresent = $attendanceSummary->present_count + $attendanceSummary->late_count;

        // Calculate adjusted attendance rate
        $adjustedRate = ($daysPresent / $elapsedSchoolDays) * 100;

        // Create a more concise description based on the image example
        // Simplified status description
        $description = match (true) {
            $isTermCompleted => 'Completed - Term attendance',
            $termProgress >= 90 => 'Term ending soon',
            $termProgress >= 50 => 'Mid-Term in progress',
            $termProgress >= 25 => 'Term in progress',
            default => 'Term started'
        };

        $color = match (true) {
            $adjustedRate >= 90 => 'success',    // Excellent attendance
            $adjustedRate >= 75 => 'info',       // Good attendance
            $adjustedRate >= 60 => 'warning',    // Fair attendance
            default => 'danger'                  // Poor attendance
        };

        return Stat::make('Attendance Rate', number_format($adjustedRate, 1) . '%')
            ->description($description)
            ->descriptionIcon('heroicon-m-calendar')
            ->color($color)
            ->chart([$adjustedRate]);
    }


    protected function getCurrentRankingStat(): Stat
    {
        // First try to get current term's report card
        $currentReport = ReportCard::query()
            ->where('student_id', $this->student->id)
            ->whereIn('status', ['final', 'published'])
            ->where('academic_session_id', config('app.current_session')->id)
            ->where('term_id', config('app.current_term')->id)
            ->where('class_size', '>', 0)
            ->first();

        // If no current report, get the most recent previous report
        if (!$currentReport) {
            $previousReport = ReportCard::query()
                ->where('student_id', $this->student->id)
                ->whereIn('status', ['final', 'published'])
                ->where('class_size', '>', 0)
                ->latest()
                ->first();

            if (!$previousReport) {
                return $this->getNullRankingStat();
            }

            // Format the term information for previous report
            $termInfo = "{$previousReport->academicSession->name} - {$previousReport->term->name}";

            return $this->formatRankingStat([
                'rank' => (int) preg_replace('/[^0-9]/', '', $previousReport->position),
                'total' => (int) $previousReport->class_size,
                'average' => $previousReport->average_score ?? 0,
            ], "Previous ($termInfo)");
        }

        // Return current term ranking
        return $this->formatRankingStat([
            'rank' => (int) preg_replace('/[^0-9]/', '', $currentReport->position),
            'total' => (int) $currentReport->class_size,
            'average' => $currentReport->average_score ?? 0,
        ], 'Current Term');
    }

    protected function getNullRankingStat(): Stat
    {
        return Stat::make('Class Ranking', 'Not Available')
            ->description('No ranking data available yet')
            ->descriptionIcon('heroicon-m-academic-cap')
            ->color('gray')
            ->chart([0]);
    }


    protected function formatRankingStat(array $data, string $type): Stat
    {
        // Calculate percentile ranking
        $percentile = (($data['total'] - $data['rank'] + 1) / $data['total']) * 100;

        // Performance level based on percentile
        $performance = match (true) {
            $percentile >= 75 => 'Top Quarter',
            $percentile >= 50 => 'Top Half',
            $percentile >= 25 => 'Below Avg',
            default => 'Needs Imp.'
        };

        // Color based solely on performance percentile
        $color = match (true) {
            $percentile >= 75 => 'success',   // Consistently green for top performers
            $percentile >= 50 => 'info',      // Blue for above average
            $percentile >= 25 => 'warning',   // Yellow for below average
            default => 'danger'               // Red for bottom performers
        };

        // Format the term type to be more concise
        $termInfo = str_contains($type, 'Previous')
            ? $this->formatTermInfo($type)
            : $type;

        return Stat::make(
            'Class Ranking',
            $this->getOrdinal($data['rank']) . ' of ' . $data['total']
        )
            ->description("$termInfo - $performance")
            ->descriptionIcon('heroicon-m-academic-cap')
            ->extraAttributes([
                'class' => 'overflow-hidden',
                'style' => '
                    max-width: 100%;
                    min-width: 0;
                    white-space: nowrap;
                    text-overflow: ellipsis;
                    font-size: 0.875rem;
                '
            ])
            ->color($color);
    }


    protected function getBestSubjectStat(): Stat
    {
        // Try to get current term's report card first
        $currentReport = ReportCard::query()
            ->where('student_id', $this->student->id)
            ->whereIn('status', ['final', 'published'])
            ->where('academic_session_id', config('app.current_session')->id)
            ->where('term_id', config('app.current_term')->id)
            ->first();

        // If no current report, get the most recent previous report
        if (!$currentReport || empty($currentReport->subject_scores)) {
            $previousReport = ReportCard::query()
                ->where('student_id', $this->student->id)
                ->whereIn('status', ['final', 'published'])
                ->latest()
                ->first();

            if (!$previousReport || empty($previousReport->subject_scores)) {
                return Stat::make('Best Subject', 'No Data')
                    ->description('No subject grades yet')
                    ->color('gray');
            }

            $subjects = collect($previousReport->subject_scores)->sortByDesc('total');
            $bestSubject = $subjects->first();
            $termInfo = "{$previousReport->academicSession->name} - {$previousReport->term->name}";

            return $this->formatSubjectStat(
                'Best Subject',
                $bestSubject,
                "Previous ($termInfo)",
                true
            );
        }

        // Return current term best subject
        $subjects = collect($currentReport->subject_scores)->sortByDesc('total');
        return $this->formatSubjectStat(
            'Best Subject',
            $subjects->first(),
            'Current Term',
            false
        );
    }

    protected function getWorstSubjectStat(): Stat
    {
        // Try to get current term's report card first
        $currentReport = ReportCard::query()
            ->where('student_id', $this->student->id)
            ->whereIn('status', ['final', 'published'])
            ->where('academic_session_id', config('app.current_session')->id)
            ->where('term_id', config('app.current_term')->id)
            ->first();

        // If no current report, get the most recent previous report
        if (!$currentReport || empty($currentReport->subject_scores)) {
            $previousReport = ReportCard::query()
                ->where('student_id', $this->student->id)
                ->whereIn('status', ['final', 'published'])
                ->latest()
                ->first();

            if (!$previousReport || empty($previousReport->subject_scores)) {
                return Stat::make('Weakest Subject', 'No Data')
                    ->description('No subject grades yet')
                    ->color('gray');
            }

            $subjects = collect($previousReport->subject_scores)->sortBy('total');
            $worstSubject = $subjects->first();
            $termInfo = "{$previousReport->academicSession->name} - {$previousReport->term->name}";

            return $this->formatSubjectStat(
                'Weakest Subject',
                $worstSubject,
                "Previous ($termInfo)",
                true
            );
        }

        // Return current term worst subject
        $subjects = collect($currentReport->subject_scores)->sortBy('total');
        return $this->formatSubjectStat(
            'Weakest Subject',
            $subjects->first(),
            'Current Term',
            false
        );
    }


    protected function formatSubjectStat(string $label, array $subject, string $term, bool $isPrevious): Stat
    {
        // Format subject name, handling Arabic if present
        $subjectName = $subject['name_ar']
            ? "{$subject['name']} ({$subject['name_ar']})"
            : $subject['name'];

        // Format term info using shortened format
        $termInfo = $isPrevious
            ? $this->formatTermInfo($term)
            : $term;

        // Create description with concise term info and performance
        $description = "{$termInfo} - {$subject['total']}% - {$subject['remark']}";

        // Consistent color coding based on performance score
        $color = match (true) {
            $subject['total'] >= 70 => 'success',  // Excellent performance
            $subject['total'] >= 60 => 'info',     // Good performance
            $subject['total'] >= 50 => 'warning',  // Fair performance
            default => 'danger'                    // Poor performance
        };

        return Stat::make($label, $subjectName)
            ->description($description)
            ->descriptionIcon('heroicon-m-academic-cap')
            ->extraAttributes([
                'class' => 'overflow-hidden',
                'style' => '
                    max-width: 100%;
                    min-width: 0;
                    white-space: nowrap;
                    text-overflow: ellipsis;
                    font-size: 0.875rem;
                '
            ])
            ->color($color);
    }

    // Helper method to determine text size based on content length
    protected function getTextSizeClass(string $content): string
    {
        $length = mb_strlen($content);

        return match (true) {
            $length > 30 => 'text-xs', // Very long text
            $length > 14 => 'text-sm', // Moderately long text
            default => '' // Default text size for short text
        };
    }

    protected function formatTermInfo(string $term): string
    {
        // Extract year and term from format like "Previous (2024/2025 - First Term)"
        if (preg_match('/(\d{4})\/(\d{4})\s*-\s*(.*?)\s*Term/', $term, $matches)) {
            $startYear = substr($matches[1], -2); // Get last 2 digits of first year
            $endYear = substr($matches[2], -2);   // Get last 2 digits of second year
            $termName = $this->shortenTermName($matches[3]);  // Convert "First" to "1st"

            return "{$startYear}/{$endYear} {$termName} Term"; // More explicit with "Term"
        }

        return $term; // Return original if no match
    }

    protected function shortenTermName(string $term): string
    {
        return match (strtolower($term)) {
            'first' => '1st',
            'second' => '2nd',
            'third' => '3rd',
            default => $term
        };
    }

    protected function getPerformanceColor(float $score): string
    {
        return match (true) {
            $score >= 70 => 'success',  // Excellent performance
            $score >= 60 => 'info',     // Good performance
            $score >= 50 => 'warning',  // Fair performance
            default => 'danger'         // Poor performance
        };
    }

    // Comment out or remove the getAssignmentCompletionStat method
    /*
    protected function getAssignmentCompletionStat(): Stat
    {
        return Stat::make('Assignment Completion', 'Coming Soon')
            ->description('Feature in development')
            ->color('gray');
    }
    */

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
