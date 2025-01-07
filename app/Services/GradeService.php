<?php

namespace App\Services;

use App\Models\Term;
use App\Models\User;
use App\Models\Staff;
use App\Models\Status;
use App\Models\Student;
use App\Models\GradingScale;
use App\Models\StudentGrade;
use App\Models\AssessmentType;
use App\Services\StatusService;
use App\Models\AttendanceRecord;
use App\Models\StudentTermTrait;
use App\Models\AttendanceSummary;
use App\Models\StudentTermComment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\StudentTermActivity;
use Illuminate\Container\Attributes\Log;
use Illuminate\Support\Facades\Storage;

class GradeService
{

    protected $statusService;
    protected $calendarService;

    public function __construct(StatusService $statusService, CalendarService $calendarService)
    {
        $this->statusService = $statusService;
        $this->calendarService = $calendarService;
    }


    /**
     * Calculate a student's average grade for a specific subject
     * 
     * @param Student $student The student to calculate grades for
     * @param int $subjectId The subject ID
     * @param int $termId The academic term ID
     * @param int $academicSessionId The academic session/year ID
     * @return array Contains average, grade, remarks and assessment scores
     */

    public function calculateSubjectAverage(
        Student $student,
        int $subjectId,
        int $termId,
        int $academicSessionId
    ): array {
        // Get all grades for this subject and student
        $grades = StudentGrade::where([
            'student_id' => $student->id,
            'subject_id' => $subjectId,
            'term_id' => $termId,
            'academic_session_id' => $academicSessionId,
            'is_published' => true
        ])
            ->with('assessmentType')
            ->get();

        // Initialize assessment scores
        $assessmentTypes = AssessmentType::where('school_id', $student->school_id)
            ->where('is_active', true)
            ->get();

        $assessmentScores = [];
        $totalScore = 0;

        // Initialize all assessment types with default '-'
        foreach ($assessmentTypes as $type) {
            $assessmentScores[strtolower($type->code)] = '-';
        }

        // Calculate total directly from raw scores
        foreach ($grades as $grade) {
            $assessmentType = $grade->assessmentType;
            if ($assessmentType) {
                $code = strtolower($assessmentType->code);
                $assessmentScores[$code] = number_format($grade->score, 1);

                // Add raw score to total
                $totalScore += $grade->score;
            }
        }

        // Get grade scale for the total score
        $gradeScale = GradingScale::getGrade($totalScore, $student->school_id);

        return [
            'average' => $totalScore, // Now returning the actual total
            'grade' => $gradeScale?->grade ?? 'N/A',
            'remark' => $gradeScale?->remark ?? 'Not Available',
            'assessment_columns' => $assessmentScores
        ];
    }



    /**
     * Generate comprehensive term report for a student
     * 
     * @param Student $student Student to generate report for
     * @param int $termId Term period
     * @param int $academicSessionId Academic session
     * @return array Comprehensive report data
     */
    public function generateTermReport(Student $student, int $termId, int $academicSessionId): array
    {
        // Get school days and attendance
        $schoolDays = $this->calendarService->getSchoolDays($student->school, Term::find($termId));
        $attendanceSummary = AttendanceSummary::calculateForStudent(
            $student,
            $academicSessionId,
            $termId,
            $schoolDays['total_days']
        );

        // Get all subjects the student has grades for
        $grades = StudentGrade::where([
            'student_id' => $student->id,
            'term_id' => $termId,
            'academic_session_id' => $academicSessionId,
            'is_published' => true
        ])
            ->select('subject_id')
            ->distinct()
            ->with('subject')
            ->get();

        // Calculate results for each subject
        $subjectResults = [];
        $totalScore = 0;
        $validSubjectCount = 0;
        $totalPercentage = 0;

        foreach ($grades as $grade) {
            $result = $this->calculateSubjectAverage(
                $student,
                $grade->subject_id,
                $termId,
                $academicSessionId
            );

            if ($result['average'] > 0) {
                $subjectResults[] = [
                    'name' => $grade->subject->name,
                    'name_ar' => $grade->subject->name_ar,
                    'assessment_columns' => $result['assessment_columns'],
                    'total' => $result['average'],
                    'grade' => $result['grade'],
                    'remark' => $result['remark']
                ];

                $totalScore += $result['average'];
                $validSubjectCount++;
                $totalPercentage += $result['average'];
            }
        }

        // Calculate class statistics
        $classStats = $this->calculateClassStatistics(
            $student->class_room_id,
            $termId,
            $academicSessionId
        );

        // Calculate student's position
        $position = $this->calculateStudentPosition(
            $student,
            $totalPercentage,
            $termId,
            $academicSessionId
        );

        // Get activities and traits
        $activities = StudentTermActivity::where([
            'student_id' => $student->id,
            'academic_session_id' => $academicSessionId,
            'term_id' => $termId,
        ])->with('activityType')->get()->map(fn($activity) => [
            'name' => $activity->activityType->name,
            'rating' => $activity->rating,
            'performance' => $this->getRatingPerformance($activity->rating),
            'remark' => $activity->remark
        ])->toArray();

        $behavioralTraits = StudentTermTrait::where([
            'student_id' => $student->id,
            'academic_session_id' => $academicSessionId,
            'term_id' => $termId,
        ])->with('behavioralTrait')->get()->map(fn($trait) => [
            'name' => $trait->behavioralTrait->name,
            'rating' => $trait->rating,
            'performance' => $this->getRatingPerformance($trait->rating),
            'remark' => $trait->remark
        ])->toArray();

        // Calculate overall average
        $overallAverage = $validSubjectCount > 0 ? round($totalPercentage / $validSubjectCount, 2) : 0;
        $gradeScale = GradingScale::getGrade($overallAverage, $student->school_id);

        // Get next term's start date (resumption date)
        $nextTermDate = Term::where(function ($query) use ($termId, $academicSessionId) {
            // Try to find next term in same session
            $query->where('academic_session_id', $academicSessionId)
                ->where('id', '>', $termId);
        })
        ->orWhere(function ($query) use ($academicSessionId) {
            // Or find first term of next session
            $query->whereHas('academicSession', function ($q) use ($academicSessionId) {
                $q->where('id', '>', $academicSessionId)
                    ->orderBy('start_date', 'asc');
            });
        })
        ->orderBy('start_date', 'asc')
        ->first();

        return [
            'basic_info' => [
                'admission' => $student->admission,
                'class' => $student->classRoom->name,
                'id' => $student->id
            ],
            'attendance' => [
                'school_days' => $schoolDays['total_days'],
                'present' => $attendanceSummary->present_count,
                'absent' => $attendanceSummary->absent_count,
                'late' => $attendanceSummary->late_count,
                'excused' => $attendanceSummary->excused_count,
                'attendance_percentage' => $attendanceSummary->attendance_percentage
            ],
            'academic_info' => [
                'session' => [
                    'id' => $academicSessionId,
                    'name' => $student->school->academicSessions->find($academicSessionId)->name
                ],
                'term' => [
                    'id' => $termId,
                    'name' => $student->school->terms->find($termId)->name
                ]
            ],
            'subjects' => $subjectResults,
            'summary' => [
                'total_subjects' => $validSubjectCount,
                'total_score' => round($totalScore, 2),
                'average' => $overallAverage,
                'grade' => $gradeScale?->grade ?? 'N/A',
                'remark' => $gradeScale?->remark ?? 'Not Available',
                'position' => $position,
                'class_size' => $classStats['total_students'],
                'class_average' => $classStats['class_average'],
                'highest_average' => $classStats['highest_average'],
                'lowest_average' => $classStats['lowest_average'],
                'resumption_date' => $nextTermDate ? 
                    $nextTermDate->start_date->format('jS F, Y') : 
                    'To be announced',
                'class_stats' => $classStats,
                'attendance_percentage' => round($attendanceSummary->attendance_percentage) . '%',
                'school_days' => $schoolDays['total_days'],
            ],
            'activities' => $activities,
            'behavioral_traits' => $behavioralTraits,
            'comments' => $this->formatComments($student, $termId, $academicSessionId)
        ];
    }


    protected function getMonthlyAttendance(Student $student, int $academicSessionId, int $termId): array
    {

        return AttendanceRecord::query()
            ->where([
                'student_id' => $student->id,
                'academic_session_id' => $academicSessionId,
                'term_id' => $termId,
            ])
            ->get()
            ->groupBy(function ($record) {
                return $record->date->format('F Y'); // Group by month name and year
            })
            ->map(function ($records) {
                return [
                    'total_days' => $records->count(),
                    'present' => $records->where('status', 'present')->count(),
                    'absent' => $records->where('status', 'absent')->count(),
                    'late' => $records->where('status', 'late')->count(),
                    'excused' => $records->where('status', 'excused')->count(),
                    'attendance_rate' => $records->count() > 0
                        ? (($records->where('status', 'present')->count() +
                            $records->where('status', 'late')->count()) /
                            $records->count()) * 100
                        : 0
                ];
            })
            ->toArray();
    }

    // Add helper method to convert rating to performance text
    public function getRatingPerformance(int $rating): string
    {
        return match ($rating) {
            1 => 'Poor',
            2 => 'Fair',
            3 => 'Good',
            4 => 'Very Good',
            5 => 'Excellent',
            default => 'N/A'
        };
    }

    protected function formatComments(Student $student, int $termId, int $academicSessionId): array
    {
        $termComment = StudentTermComment::where([
            'student_id' => $student->id,
            'academic_session_id' => $academicSessionId,
            'term_id' => $termId,
        ])->first();

        if (!$termComment) {
            return [];
        }

        $teacherStaff = null;
        $principalStaff = null;

        // Safely get teacher staff
        if ($termComment->class_teacher_id) {
            $teacherUser = User::find($termComment->class_teacher_id);
            if ($teacherUser && $teacherUser->staff) {
                $teacherStaff = $teacherUser->staff;
            }
        }

        // Safely get principal staff
        if ($termComment->principal_id) {
            $principalUser = User::find($termComment->principal_id);
            if ($principalUser && $principalUser->staff) {
                $principalStaff = $principalUser->staff;
            }
        }

        return [
            'class_teacher' => [
                'comment' => $termComment->class_teacher_comment,
                'digital_signature' => [
                    'name' => $teacherStaff ? $teacherStaff->full_name : 'Class Teacher',
                    'signature_url' => $teacherStaff && $teacherStaff->signature
                        ? Storage::disk('public')->url($teacherStaff->signature)
                        : null,
                    'date' => $termComment->created_at?->format('d/m/Y')
                ],
                'manual_signature' => [
                    'enabled' => !$teacherStaff || !$teacherStaff->signature,
                    'label' => "Class Teacher's Signature",
                    'date' => true
                ]
            ],
            'principal' => [
                'comment' => $termComment->principal_comment,
                'digital_signature' => [
                    'name' => $principalStaff ? $principalStaff->full_name : 'Principal',
                    'signature_url' => $principalStaff && $principalStaff->signature
                        ? Storage::disk('public')->url($principalStaff->signature)
                        : null,
                    'date' => $termComment->created_at?->format('d/m/Y')
                ],
                'manual_signature' => [
                    'enabled' => !$principalStaff || !$principalStaff->signature,
                    'label' => "Principal's Signature",
                    'date' => true
                ]
            ]
        ];
    }

    /**
     * Calculate class-wide statistics
     * 
     * @param int $classRoomId Class room ID
     * @param int $termId Term period
     * @param int $academicSessionId Academic session
     * @return array Class statistics
     */
    protected function calculateClassStatistics(
        int $classRoomId,
        int $termId,
        int $academicSessionId
    ): array {
        $activeStatusId = $this->statusService->getActiveStudentStatusId();

        // Get total number of active students
        $totalStudents = Student::where('class_room_id', $classRoomId)
            ->where('status_id', $activeStatusId)
            ->count();

        // Get all student grades in the class
        $students = Student::where('class_room_id', $classRoomId)
            ->where('status_id', $activeStatusId)
            ->get();

        $averages = collect();

        foreach ($students as $student) {
            $grades = StudentGrade::where([
                'student_id' => $student->id,
                'term_id' => $termId,
                'academic_session_id' => $academicSessionId,
                'is_published' => true
            ])
                ->with('assessmentType')
                ->get();

            $totalScore = 0;
            $totalWeight = 0;

            foreach ($grades as $grade) {
                $weight = $grade->assessmentType?->weight ?? 1;
                $totalScore += ($grade->score * $weight);
                $totalWeight += $weight;
            }

            if ($totalWeight > 0) {
                $averages->push($totalScore / $totalWeight);
            }
        }

        return [
            'total_students' => $totalStudents,
            'class_average' => $averages->isNotEmpty() ? round($averages->avg(), 2) : 0,
            'highest_average' => $averages->isNotEmpty() ? round($averages->max(), 2) : 0,
            'lowest_average' => $averages->isNotEmpty() ? round($averages->min(), 2) : 0
        ];
    }

    /**
     * Calculate student's position in class
     */
    protected function calculateStudentPosition(
        Student $student,
        float $studentAverage,
        int $termId,
        int $academicSessionId
    ): string {
        // Get all students in the same class
        $students = Student::where('class_room_id', $student->class_room_id)
            ->where('status_id', $this->statusService->getActiveStudentStatusId())
            ->get();

        // Calculate averages for all students
        $averages = collect();
        foreach ($students as $classStudent) {
            $totalScore = 0;
            $totalWeight = 0;

            // Get all published grades for the student
            $grades = StudentGrade::where([
                'student_id' => $classStudent->id,
                'term_id' => $termId,
                'academic_session_id' => $academicSessionId,
                'class_room_id' => $student->class_room_id,
                'is_published' => true
            ])
                ->with('assessmentType')
                ->get();

            // Calculate weighted average
            foreach ($grades as $grade) {
                $weight = $grade->assessmentType?->weight ?? 1;
                $totalScore += ($grade->score * $weight);
                $totalWeight += $weight;
            }

            if ($totalWeight > 0) {
                $averages->push([
                    'student_id' => $classStudent->id,
                    'average' => $totalScore / $totalWeight
                ]);
            }
        }

        // Sort averages in descending order
        $sorted = $averages->sortByDesc('average')->values();

        // Find student's position
        $position = $sorted->search(function ($item) use ($student) {
            return $item['student_id'] === $student->id;
        });

        // Convert to ordinal number (1st, 2nd, 3rd, etc.)
        return $this->getOrdinalNumber($position + 1);
    }

    protected function getOrdinalNumber(int $number): string
    {
        if (!in_array(($number % 100), array(11, 12, 13))) {
            switch ($number % 10) {
                case 1:
                    return $number . 'st';
                case 2:
                    return $number . 'nd';
                case 3:
                    return $number . 'rd';
            }
        }
        return $number . 'th';
    }

    // In app/Services/GradeService.php

    public function getStudentTermSummary(Student $student, int $termId, int $academicSessionId): array
    {
        // Get all assessments for this student in the specified term
        $assessments = StudentGrade::where([
            'student_id' => $student->id,
        ])->with(['assessment.subject', 'assessment.assessmentType'])->get();

        // Calculate total score and average
        $totalScore = 0;
        $subjectCount = 0;
        $subjects = [];

        foreach ($assessments as $grade) {
            $subject = $grade->assessment->subject;
            if (!isset($subjects[$subject->id])) {
                $subjects[$subject->id] = [
                    'total' => 0,
                    'count' => 0,
                ];
            }

            $subjects[$subject->id]['total'] += $grade->score;
            $subjects[$subject->id]['count']++;

            if ($subjects[$subject->id]['count'] === $grade->assessment->assessmentType->count()) {
                $totalScore += ($subjects[$subject->id]['total'] / $subjects[$subject->id]['count']);
                $subjectCount++;
            }
        }

        $average = $subjectCount > 0 ? round($totalScore / $subjectCount, 2) : 0;

        // Calculate position
        $position = $this->calculateStudentPosition(
            $student,
            $average,
            $termId,
            $academicSessionId
        );

        // Get class size
        $activeStatusId = $this->statusService->getActiveStudentStatusId();
        $classSize = Student::where('class_room_id', $student->class_room_id)
            ->where('status_id', $activeStatusId)
            ->count();

        // Get class statistics
        $classStats = $this->calculateClassStatistics(
            $student->class_room_id,
            $termId,
            $academicSessionId
        );

        return [
            'total_score' => round($totalScore, 2),
            'average' => $average,
            'position' => $position,
            'class_size' => $classSize,
            'class_stats' => $classStats
        ];
    }
}
