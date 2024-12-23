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
use App\Models\SubjectAssessment;
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


    public function calculateSubjectAverage(
        Student $student,      // The student whose grades we're calculating
        int $subjectId,       // The subject's ID
        int $termId,          // The academic term ID
        int $academicSessionId // The academic session/year ID
    ): array {
        // Get all assessments for this subject in the specified term
        $assessments = SubjectAssessment::where([
            'subject_id' => $subjectId,
            'term_id' => $termId,
            'academic_session_id' => $academicSessionId,
            'class_room_id' => $student->class_room_id,  // Student's class
        ])
            ->with(['assessmentType', 'grades' => function ($query) use ($student) {
                $query->where('student_id', $student->id);
            }])
            ->get();

        // Initialize dynamic scores array
        $assessmentTypes = AssessmentType::where('school_id', $student->school_id)
            // ->orderBy('position')
            ->get();

        $assessmentScores = [];
        $totalScore = 0;
        $totalWeight = 0;

        // Initialize all assessment types with default '-'
        foreach ($assessmentTypes as $type) {
            $assessmentScores[strtolower($type->code)] = '-';
        }

        // Fill in actual scores where they exist
        foreach ($assessments as $assessment) {
            $grade = $assessment->grades->first();
            if ($grade) {
                $code = strtolower($assessment->assessmentType->code);
                $assessmentScores[$code] = number_format($grade->score, 1);
                $totalScore += $grade->score;
                $totalWeight += $assessment->assessmentType->weight;
            }
        }

        // Calculate final average and grade
        $finalAverage = $totalWeight > 0 ? ($totalScore / $totalWeight) * 100 : 0;
        $gradeScale = GradingScale::getGrade($finalAverage, $student->school_id);

        return [
            'average' => round($finalAverage, 2),
            'grade' => $gradeScale?->grade ?? 'N/A',
            'remark' => $gradeScale?->remark ?? 'Not Available',
            'assessment_columns' => $assessmentScores
        ];
    }



    /**
     * Generate comprehensive term report
     */
    public function generateTermReport(Student $student, int $termId, int $academicSessionId): array
    {

        // Get school days from calendar service
        $schoolDays = $this->calendarService->getSchoolDays($student->school, Term::find($termId));
        // dd($schoolDays);
        // Get or calculate attendance summary
        $attendanceSummary = AttendanceSummary::calculateForStudent(
            $student,
            $academicSessionId,
            $termId,
            $schoolDays['total_days']
        );

        $subjectAssessments = SubjectAssessment::where([
            'class_room_id' => $student->class_room_id,
            'term_id' => $termId,
            'academic_session_id' => $academicSessionId,
        ])
            ->select('subject_id')
            ->distinct()
            ->with('subject')
            ->get();

        $subjectResults = [];
        $totalScore = 0;
        $validSubjectCount = 0;
        $totalPercentage = 0;

        foreach ($subjectAssessments as $assessment) {
            $result = $this->calculateSubjectAverage(
                $student,
                $assessment->subject_id,
                $termId,
                $academicSessionId
            );

            if ($result['average'] > 0) {
                $subjectResults[] = [
                    'name' => $assessment->subject->name,
                    'name_ar' => $assessment->subject->name_ar,
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
        $activeStatusId = $this->statusService->getActiveStudentStatusId();
        // Get class size - specifically counting active students
        $classSize = Student::where('class_room_id', $student->class_room_id)
            ->where('status_id', $activeStatusId) // Assuming 1 is active status
            ->count();

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

        // Add activities from StudentTermActivity
        // Modify the activities mapping to include the performance text
        $activities = StudentTermActivity::where([
            'student_id' => $student->id,
            'academic_session_id' => $academicSessionId,
            'term_id' => $termId,
        ])->with('activityType')->get()->map(fn($activity) => [
            'name' => $activity->activityType->name,
            'rating' => $activity->rating,
            'performance' => $this->getRatingPerformance($activity->rating), // Add this
            'remark' => $activity->remark
        ])->toArray();


        // Add behavioral traits from StudentTermTrait
        $behavioralTraits = StudentTermTrait::where([
            'student_id' => $student->id,
            'academic_session_id' => $academicSessionId,
            'term_id' => $termId,
        ])->with('behavioralTrait')->get()->map(fn($trait) => [
            'name' => $trait->behavioralTrait->name,
            'rating' => $trait->rating,
            'remark' => $trait->remark
        ])->toArray();

        $behavioralTraits = StudentTermTrait::where([
            'student_id' => $student->id,
            'academic_session_id' => $academicSessionId,
            'term_id' => $termId,
        ])->with('behavioralTrait')->get()->map(fn($trait) => [
            'name' => $trait->behavioralTrait->name,
            'rating' => $trait->rating,
            'performance' => $this->getRatingPerformance($trait->rating), // Add this
            'remark' => $trait->remark
        ])->toArray();

        $overallAverage = $validSubjectCount > 0 ? round($totalPercentage / $validSubjectCount, 2) : 0;
        $gradeScale = GradingScale::getGrade($overallAverage, $student->school_id);

        return [
            'activities' => $activities,
            'behavioral_traits' => $behavioralTraits,
            'comments' => $this->formatComments($student, $termId, $academicSessionId),
            'student' => [
                'name' => $student->full_name,
                'admission_number' => $student->admission?->admission_number,
                'class' => $student->classRoom->name,
                'profile_picture' => $student->profile_picture_url,
                'admission' => $student->admission,  // Add this for template field mapping
                'id' => $student->id  // Add this for model lookup if needed
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
            'attendance' => [
                'school_days' => $attendanceSummary->total_days,
                'present' => $attendanceSummary->present_count,
                'absent' => $attendanceSummary->absent_count,
                'late' => $attendanceSummary->late_count,
                'excused' => $attendanceSummary->excused_count,
                'attendance_rate' => $attendanceSummary->attendance_percentage,
                'monthly_breakdown' => $this->getMonthlyAttendance(
                    $student,
                    $academicSessionId,
                    $termId
                )
            ],
            'subjects' => $subjectResults,
            'summary' => [
                'total_subjects' => $validSubjectCount,
                'total_score' => round($totalScore, 2),
                'average' => $overallAverage,
                'grade' => $gradeScale?->grade ?? 'N/A',
                'remark' => $gradeScale?->remark ?? 'Not Available',
                'position' => $position,
                'class_size' => $classSize,  // Add class size here
                'class_stats' => [
                    'class_average' => $classStats['class_average'],
                    'highest_average' => $classStats['highest_average'],
                    'lowest_average' => $classStats['lowest_average'],
                    'total_students' => $classSize  // Ensure consistency with class size
                ]
            ]
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

    protected function calculateClassStatistics(
        int $classRoomId,
        int $termId,
        int $academicSessionId
    ): array {

        $activeStatusId = $this->statusService->getActiveStudentStatusId();
        // First, get the total number of active students in the class
        $totalStudents = Student::where('class_room_id', $classRoomId)
            ->where('status_id', $activeStatusId) // Assuming 1 is active status
            ->count(); // Get actual count of students

        $students = Student::where('class_room_id', $classRoomId)
            ->where('status_id', $activeStatusId)
            ->get();

        $averages = collect();

        foreach ($students as $student) {
            $totalScore = 0;
            $subjectCount = 0;

            $assessments = SubjectAssessment::where([
                'class_room_id' => $classRoomId,
                'term_id' => $termId,
                'academic_session_id' => $academicSessionId,
                'is_published' => true,
            ])->get();

            foreach ($assessments as $assessment) {
                $grade = StudentGrade::where([
                    'student_id' => $student->id,
                    'subject_assessment_id' => $assessment->id,
                ])->first();

                if ($grade) {
                    $totalScore += $grade->score;
                    $subjectCount++;
                }
            }

            if ($subjectCount > 0) {
                $averages->push($totalScore / $subjectCount);
            }
        }

        return [
            'total_students' => $totalStudents, // Use actual count
            'class_average' => $averages->isNotEmpty() ? round($averages->avg(), 2) : 0,
            'highest_average' => $averages->isNotEmpty() ? round($averages->max(), 2) : 0,
            'lowest_average' => $averages->isNotEmpty() ? round($averages->min(), 2) : 0,
        ];
    }

    protected function calculateStudentPosition(
        Student $student,
        float $studentAverage,
        int $termId,
        int $academicSessionId
    ): string {
        // Get all students in the same class
        $students = Student::where('class_room_id', $student->class_room_id)
            ->get();

        // Calculate averages for all students
        $averages = collect();
        foreach ($students as $classStudent) {
            $total = 0;
            $count = 0;

            $assessments = SubjectAssessment::where([
                'class_room_id' => $student->class_room_id,
                'term_id' => $termId,
                'academic_session_id' => $academicSessionId,
            ])->get();

            foreach ($assessments as $assessment) {
                $grade = StudentGrade::where([
                    'student_id' => $classStudent->id,
                    'subject_assessment_id' => $assessment->id,
                ])->first();

                if ($grade) {
                    $total += $grade->score;
                    $count++;
                }
            }

            if ($count > 0) {
                $averages->push([
                    'student_id' => $classStudent->id,
                    'average' => $total / $count
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
