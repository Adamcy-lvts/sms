<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Term;
use App\Models\School;
use App\Models\Status;
use App\Models\Student;
use App\Models\ClassRoom;
use Illuminate\Support\Str;
use App\Models\AcademicSession;
use Illuminate\Database\Seeder;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSummary;
use App\Services\CalendarService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceSeeder extends Seeder
{
    protected $calendarService;

    // Define base attendance patterns
    protected $attendancePatterns = [
        'excellent' => [
            'present' => 90,
            'late' => 5,
            'absent' => 3,
            'excused' => 2
        ],
        'good' => [
            'present' => 80,
            'late' => 10,
            'absent' => 5,
            'excused' => 5
        ],
        'average' => [
            'present' => 70,
            'late' => 15,
            'absent' => 10,
            'excused' => 5
        ],
        'poor' => [
            'present' => 60,
            'late' => 20,
            'absent' => 15,
            'excused' => 5
        ]
    ];

    // Probability modifiers for different term types
    protected $termModifiers = [
        'First Term' => [
            'present' => 1.1, // Higher attendance in first term
            'late' => 0.9,
            'absent' => 0.8,
            'excused' => 1.0
        ],
        'Second Term' => [
            'present' => 1.0, // Normal attendance
            'late' => 1.0,
            'absent' => 1.0,
            'excused' => 1.0
        ],
        'Third Term' => [
            'present' => 0.9, // Lower attendance due to exam stress/fatigue
            'late' => 1.2,
            'absent' => 1.1,
            'excused' => 1.2
        ]
    ];

    // Weather patterns that affect attendance
    protected $weatherPatterns = [
        'rainy_season' => [
            'months' => [6, 7, 8, 9], // June to September
            'modifiers' => [
                'present' => 0.9,
                'late' => 1.3,
                'absent' => 1.2,
                'excused' => 1.0
            ]
        ],
        'harmattan' => [
            'months' => [11, 12, 1], // November to January
            'modifiers' => [
                'present' => 0.95,
                'late' => 1.1,
                'absent' => 1.15,
                'excused' => 1.2
            ]
        ]
    ];

    public function __construct(CalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    public function run(): void
    {
        DB::transaction(function () {
            try {
                // Get Khalil Integrated Academy
                $school = School::findOrFail(2);
                Log::info("Starting attendance seeding for {$school->name}");

                // Get all academic sessions ordered by date
                $sessions = $school->academicSessions()
                    ->with('terms')
                    ->orderBy('start_date')
                    ->get();

                // Get all students with their classes
                $students = Student::where('school_id', $school->id)
                    ->with('classRoom')
                    ->get();

                foreach ($sessions as $session) {
                    Log::info("Processing session: {$session->name}");

                    foreach ($session->terms as $term) {
                        $this->createTermAttendance($school, $term, $students, $session);
                    }
                }

                Log::info("Completed attendance seeding successfully");
            } catch (\Exception $e) {
                Log::error("Error seeding attendance: " . $e->getMessage());
                throw $e;
            }
        });
    }

    protected function createTermAttendance(School $school, Term $term, $students, $session): void
    {
        Log::info("Creating attendance for term: {$term->name}");

        // Get school days excluding holidays
        $schoolDays = $this->calendarService->getSchoolDays($school, $term);

        foreach ($students as $student) {
            // Get base pattern based on academic performance
            $basePattern = $this->getStudentPattern($student);

            // Create daily attendance
            $current = Carbon::parse($term->start_date);
            $end = Carbon::parse($term->end_date);

            while ($current <= $end) {
                if (!$this->isHoliday($current, $schoolDays['excluded_dates'])) {
                    // Apply modifiers to base pattern
                    $modifiedPattern = $this->applyModifiers(
                        $basePattern,
                        $term,
                        $current,
                        $student
                    );

                    $this->createDailyAttendance(
                        $student,
                        $term,
                        $current,
                        $modifiedPattern,
                        $session
                    );
                }
                $current->addDay();
            }

            // Generate term summary
            $this->createAttendanceSummary(
                $student,
                $term,
                $session,
                $schoolDays['total_days']
            );
        }
    }

    protected function isHoliday(Carbon $date, array $excludedDates): bool
    {
        return $date->isWeekend() || in_array($date->format('Y-m-d'), $excludedDates);
    }

    protected function applyModifiers(array $basePattern, Term $term, Carbon $date, Student $student): array
    {
        $pattern = $basePattern;

        // Apply term-specific modifiers
        if (isset($this->termModifiers[$term->name])) {
            foreach ($pattern as $status => &$probability) {
                $probability *= $this->termModifiers[$term->name][$status];
            }
        }

        // Apply weather modifiers
        $month = $date->month;
        foreach ($this->weatherPatterns as $season) {
            if (in_array($month, $season['months'])) {
                foreach ($pattern as $status => &$probability) {
                    $probability *= $season['modifiers'][$status];
                }
                break;
            }
        }

        // Special cases for specific class types
        if (str_contains($student->classRoom->name, 'JSS 1')) {
            // New students tend to have more attendance issues
            $pattern['present'] *= 0.95;
            $pattern['late'] *= 1.2;
        } elseif (str_contains($student->classRoom->name, 'SSS 3')) {
            // Final year students tend to have better attendance
            $pattern['present'] *= 1.1;
            $pattern['late'] *= 0.9;
        }

        // Normalize probabilities to ensure they sum to 100
        $total = array_sum($pattern);
        array_walk($pattern, function (&$value) use ($total) {
            $value = ($value / $total) * 100;
        });

        return $pattern;
    }

    protected function createDailyAttendance(
        Student $student,
        Term $term,
        Carbon $date,
        array $pattern,
        AcademicSession $session
    ): void {
        // Determine status based on probability
        $rand = rand(1, 100);
        $cumulative = 0;
        $status = 'present'; // Default status

        foreach ($pattern as $currentStatus => $probability) {
            $cumulative += $probability;
            if ($rand <= $cumulative) {
                $status = $currentStatus;
                break;
            }
        }

        AttendanceRecord::create([
            'school_id' => $student->school_id,
            'class_room_id' => $student->class_room_id,
            'student_id' => $student->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'date' => $date,
            'status' => $status,
            'remarks' => $this->getStatusRemark($status),
            'recorded_by' => 1,
        ]);
    }

    protected function createAttendanceSummary(
        Student $student,
        Term $term,
        AcademicSession $session,
        int $totalDays
    ): void {
        AttendanceSummary::calculateForStudent(
            $student,
            $session->id,
            $term->id,
            $totalDays
        );
    }

    protected function getStudentPattern(Student $student): array
    {
        // Use academic performance and behavioral traits if available
        $averageGrade = $student->grades()->avg('score') ?? 0;
        $behaviorScore = $student->termTraits()->avg('rating') ?? 0;

        // Combine academic and behavioral scores
        $combinedScore = ($averageGrade * 0.7) + ($behaviorScore * 30);

        return match (true) {
            $combinedScore >= 85 => $this->attendancePatterns['excellent'],
            $combinedScore >= 75 => $this->attendancePatterns['good'],
            $combinedScore >= 60 => $this->attendancePatterns['average'],
            default => $this->attendancePatterns['poor']
        };
    }

    protected function getStatusRemark(string $status): ?string
    {
        $remarks = [
            'late' => [
                'Traffic delay on way to school',
                'Public transport issues',
                'Overslept',
                'Family emergency delayed arrival',
                'Weather-related transport delay'
            ],
            'absent' => [
                'Not feeling well',
                'Family emergency',
                'No information provided',
                'Medical appointment',
                'Personal reasons'
            ],
            'excused' => [
                'Doctor\'s appointment',
                'Family event',
                'Religious observance',
                'Official sports competition',
                'Academic competition participation'
            ]
        ];

        return isset($remarks[$status])
            ? $remarks[$status][array_rand($remarks[$status])]
            : null;
    }
}

// namespace Database\Seeders;

// use Carbon\Carbon;
// use App\Models\School;
// use App\Models\Student;
// use Illuminate\Database\Seeder;
// use App\Models\AttendanceRecord;
// use App\Models\AttendanceSummary;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

// class AttendanceSeeder extends Seeder
// {
//     public function run(): void
//     {
//         $school = School::where('slug', 'kings-private-school')->first();
//         $sessions = $school->academicSessions()->get();

//         foreach ($sessions as $session) {
//             foreach ($session->terms as $term) {
//                 $startDate = Carbon::parse($term->start_date);
//                 $endDate = Carbon::parse($term->end_date);

//                 // Get all active students
//                 $students = Student::where('school_id', $school->id)
//                     ->whereHas(
//                         'status',
//                         fn($query) =>
//                         $query->where('name', 'active')
//                     )->get();

//                 // Generate attendance for each student
//                 foreach ($students as $student) {
//                     $this->generateAttendanceForStudent(
//                         $student,
//                         $term,
//                         $startDate,
//                         $endDate
//                     );
//                 }
//             }
//         }
//     }

//     protected function generateAttendanceForStudent($student, $term, $startDate, $endDate)
//     {
//         $currentDate = $startDate->copy();
//         $presentCount = 0;
//         $absentCount = 0;
//         $lateCount = 0;
//         $excusedCount = 0;

//         while ($currentDate <= $endDate) {
//             // Skip weekends
//             if ($currentDate->isWeekend()) {
//                 $currentDate->addDay();
//                 continue;
//             }

//             // Generate random attendance status
//             $status = $this->getRandomStatus();

//             // Create attendance record
//             AttendanceRecord::create([
//                 'school_id' => $student->school_id,
//                 'class_room_id' => $student->class_room_id,
//                 'student_id' => $student->id,
//                 'academic_session_id' => $term->academic_session_id,
//                 'term_id' => $term->id,
//                 'date' => $currentDate,
//                 'status' => $status,
//                 'arrival_time' => $status === 'late' ?
//                     $currentDate->copy()->addHours(rand(1, 2)) : null,
//                 'recorded_by' => 1
//             ]);

//             // Update counters
//             switch ($status) {
//                 case 'present':
//                     $presentCount++;
//                     break;
//                 case 'absent':
//                     $absentCount++;
//                     break;
//                 case 'late':
//                     $lateCount++;
//                     break;
//                 case 'excused':
//                     $excusedCount++;
//                     break;
//             }

//             $currentDate->addDay();
//         }

//         // Create attendance summary
//         $totalDays = $presentCount + $absentCount + $lateCount + $excusedCount;
//         $attendancePercentage = ($presentCount + $lateCount) / $totalDays * 100;

//         AttendanceSummary::create([
//             'school_id' => $student->school_id,
//             'student_id' => $student->id,
//             'academic_session_id' => $term->academic_session_id,
//             'term_id' => $term->id,
//             'total_days' => $totalDays,
//             'present_count' => $presentCount,
//             'absent_count' => $absentCount,
//             'late_count' => $lateCount,
//             'excused_count' => $excusedCount,
//             'attendance_percentage' => $attendancePercentage
//         ]);
//     }

//     protected function getRandomStatus(): string
//     {
//         $rand = rand(1, 100);
//         if ($rand <= 85) return 'present';      // 85% chance
//         if ($rand <= 92) return 'late';         // 7% chance
//         if ($rand <= 97) return 'absent';       // 5% chance
//         return 'excused';                       // 3% chance
//     }
// }
