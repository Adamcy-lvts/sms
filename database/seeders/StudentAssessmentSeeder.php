<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\School;
use App\Models\Status;
use App\Models\Student;
use App\Models\Subject;
use App\Models\ClassRoom;
use App\Models\StudentGrade;
use App\Models\AssessmentType;
use App\Models\AcademicSession;
use App\Services\StatusService;
use Illuminate\Database\Seeder;
use App\Models\SubjectAssessment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Teacher;  // Add this import

class StudentAssessmentSeeder extends Seeder
{
    // Define student performance profiles for realistic grade distribution
    protected $performanceProfiles = [
        'excellent' => ['weight' => 15, 'range' => [85, 100], 'ca_range' => [8, 10]],
        'good' => ['weight' => 30, 'range' => [70, 84], 'ca_range' => [7, 9]],
        'average' => ['weight' => 35, 'range' => [55, 69], 'ca_range' => [5, 8]],
        'poor' => ['weight' => 20, 'range' => [40, 54], 'ca_range' => [4, 7]]
    ];

    // Subject correlations for realistic grades
    protected $subjectCorrelations = [
        'Mathematics' => ['Physics', 'Chemistry', 'Computer Studies'],
        'English Language' => ['Literature', 'Creative Arts'],
        'Physics' => ['Mathematics', 'Chemistry'],
        'Basic Science' => ['Mathematics', 'Agricultural Science'],
        'Computer Studies' => ['Mathematics', 'English Language']
    ];

    public function run(): void
    {
        try {
            DB::beginTransaction();

            // Debug log status table
            $statuses = Status::where('type', 'student')->get();
            Log::info('Available student statuses:', $statuses->toArray());

            $activeStatus = Status::where([
                'type' => 'student',
                'name' => 'active'
            ])->first();
            Log::info('Active status:', $activeStatus ? $activeStatus->toArray() : ['not found']);

            // Get school and validate existence
            $school = School::where('slug', 'khalil-integrated-academy')->first();
            if (!$school) {
                throw new \Exception('School not found with slug: khalil-integrated-academy');
            }

            Log::info('Found school: ' . $school->name);

            // Debug log students
            $totalStudents = Student::count();
            $activeStudents = Student::where('status_id', $activeStatus?->id)->count();
            Log::info("Total students: {$totalStudents}, Active students: {$activeStudents}");

            // Get all classes and their student counts
            $classes = ClassRoom::where('school_id', $school->id)->get();
            foreach ($classes as $class) {
                $studentsInClass = Student::where('class_room_id', $class->id)->count();
                $activeStudentsInClass = Student::where([
                    'class_room_id' => $class->id,
                    'status_id' => $activeStatus?->id
                ])->count();
                Log::info("Class {$class->name}: Total students = {$studentsInClass}, Active students = {$activeStudentsInClass}");
            }

            // Get sessions with validation
            $sessions = AcademicSession::where('school_id', $school->id)
                ->with(['terms'])
                ->orderBy('start_date')
                ->get();

            if ($sessions->isEmpty()) {
                throw new \Exception('No academic sessions found for school: ' . $school->name);
            }

            Log::info('Found sessions: ' . $sessions->count());

            // Get subjects with validation
            $subjects = Subject::where('school_id', $school->id)
                ->where('is_active', true)
                ->get();

            if ($subjects->isEmpty()) {
                throw new \Exception('No active subjects found for school: ' . $school->name);
            }

            Log::info('Found subjects: ' . $subjects->count());
            // $activeStatus = Status::where('name', 'active')->where('type', 'student')->first();
            // Get classes with validation - Modified this section
            $classes = ClassRoom::where('school_id', $school->id)
                ->with(['students' => function ($query) use($activeStatus) {
                    $query->where('status_id', $activeStatus->id);
                }])
                ->get();
            Log::info('Found classes: ' . $classes->count());
            if ($classes->isEmpty()) {
                throw new \Exception('No classes found for school: ' . $school->name);
            }

            // Remove or comment out these problematic lines
            // Log::info('Found classes: ' . $classes);
            // Log::info('Found classes: ' . $classes->students);  // This line causes the error

            // Validate that classes have students
            $classesWithStudents = $classes->filter(function ($class) {
                return $class->students->isNotEmpty();
            });
            Log::info('Found classes with students: ' . $classesWithStudents->count());
            // if ($classesWithStudents->isEmpty()) {
            //     throw new \Exception('No classes found with active students');
            // }

            // Create assessment types first
            $types = [
                [
                    'name' => 'First CA',
                    'code' => 'CA1',
                    'max_score' => 10,
                    'weight' => 10,
                ],
                [
                    'name' => 'Second CA',
                    'code' => 'CA2',
                    'max_score' => 10,
                    'weight' => 10,
                ],
                [
                    'name' => 'Third CA',
                    'code' => 'CA3',
                    'max_score' => 10,
                    'weight' => 10,
                ],
                [
                    'name' => 'Examination',
                    'code' => 'EXAM',
                    'max_score' => 70,
                    'weight' => 70,
                ]
            ];

            // Create assessment types
            $assessmentTypes = collect($types)->map(function ($type) use ($school) {
                return AssessmentType::firstOrCreate(
                    [
                        'school_id' => $school->id,
                        'code' => $type['code']
                    ],
                    $type
                );
            });

            Log::info('Created assessment types: ' . $assessmentTypes->count());

            // Generate assessments for each session and term
            foreach ($sessions as $session) {
                foreach ($session->terms as $term) {
                    foreach ($classesWithStudents as $class) {
                        $this->generateClassAssessments(
                            $school,
                            $session,
                            $term,
                            $class,
                            $subjects,
                            $assessmentTypes
                        );
                    }
                }
            }

            DB::commit();
            Log::info('Successfully completed assessment generation');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to generate assessments: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function generateClassAssessments($school, $session, $term, $class, $subjects, $assessmentTypes)
    {
        // Skip if future session
        if (Carbon::parse($session->start_date)->isFuture()) {
            return;
        }

        // Get available teachers for the school
        $teachers = Teacher::where('school_id', $school->id)->get();

        if ($teachers->isEmpty()) {
            throw new \Exception('No teachers found for school: ' . $school->name . '. Please run SubjectTeacherSeeder first.');
        }

        foreach ($subjects as $subject) {
            // Find teacher assigned to this subject
            $teacher = $teachers->first(function ($teacher) use ($subject) {
                return $teacher->subjects->contains($subject->id);
            }) ?? $teachers->random(); // Fallback to random teacher if none assigned

            // Create assessments
            foreach ($assessmentTypes as $type) {
                $assessment = SubjectAssessment::create([
                    'school_id' => $school->id,
                    'subject_id' => $subject->id,
                    'class_room_id' => $class->id,
                    'teacher_id' => $teacher->id, // Add teacher_id
                    'academic_session_id' => $session->id,
                    'term_id' => $term->id,
                    'assessment_type_id' => $type->id,
                    'title' => "{$type->name} - {$subject->name}",
                    'assessment_date' => $this->generateAssessmentDate($term, $type),
                    'is_published' => true,
                    'published_at' => now(),
                    'created_by' => 1
                ]);

                // Generate student grades
                foreach ($class->students as $student) {
                    $this->generateStudentGrade($student, $assessment, $type);
                }
            }
        }
    }

    protected function generateStudentGrade($student, $assessment, $type)
    {
        // Get student's performance profile
        $profile = $this->getStudentProfile($student);

        $score = match ($type->code) {
            'CA1', 'CA2', 'CA3' => $this->generateCAScore($student, $type->max_score, $profile),
            'EXAM' => $this->generateExamScore($student, $type->max_score, $profile),
            default => rand(1, $type->max_score)
        };

        // Ensure score doesn't exceed max_score
        $score = min($score, $type->max_score);

        StudentGrade::create([
            'school_id' => $student->school_id,
            'student_id' => $student->id,
            'subject_assessment_id' => $assessment->id,
            'score' => $score,
            'recorded_by' => 1,
            'modified_by' => 1,
            'graded_at' => now()
        ]);
    }

    protected function getStudentProfile($student)
    {
        // Deterministic but random-looking profile assignment based on student ID
        $hash = crc32($student->id . $student->admission_number);
        $value = ($hash % 100) + 1;

        $cumulative = 0;
        foreach ($this->performanceProfiles as $profile => $data) {
            $cumulative += $data['weight'];
            if ($value <= $cumulative) {
                return $profile;
            }
        }
        return 'average';
    }

    protected function generateCAScore($student, $maxScore, $profile)
    {
        // Generate CA scores with normal distribution
        $mean = $maxScore * 0.7; // Average around 70%
        $stdDev = $maxScore * 0.15; // Standard deviation 15%

        $score = $this->gaussianRandom($mean, $stdDev);
        return (int)round(min(max($score, 0), $maxScore)); // Cast to integer
    }

    protected function generateExamScore($student, $maxScore, $profile)
    {
        // Generate exam scores with slightly lower mean
        $mean = $maxScore * 0.65; // Average around 65%
        $stdDev = $maxScore * 0.2; // Wider spread for exams

        $score = $this->gaussianRandom($mean, $stdDev);
        return (int)round(min(max($score, 0), $maxScore)); // Cast to integer
    }

    protected function gaussianRandom($mean, $stdDev)
    {
        // Box-Muller transform for normal distribution
        $u1 = rand() / getrandmax();
        $u2 = rand() / getrandmax();

        $z0 = sqrt(-2.0 * log($u1)) * cos(2 * M_PI * $u2);
        return $z0 * $stdDev + $mean;
    }

    protected function generateAssessmentDate($term, $type): Carbon
    {
        $termStart = Carbon::parse($term->start_date);
        $termMid = $termStart->copy()->addDays($termStart->diffInDays($term->end_date) / 2);
        $termEnd = Carbon::parse($term->end_date);

        return match ($type->code) {
            'CA1' => $termStart->copy()->addDays(rand(14, 21)),
            'CA2' => $termMid->copy()->subDays(rand(7, 14)),
            'CA3' => $termMid->copy()->addDays(rand(7, 14)),
            'EXAM' => $termEnd->copy()->subDays(rand(7, 14)),
            default => $termMid
        };
    }

    protected function processClassAssessments($class, $sessions, $school): void
    {
        $subjects = Subject::where('school_id', $school->id)
            ->where('is_active', true)
            ->get();

        $students = $class->students;

        // Assign consistent performance profiles to students
        $studentProfiles = $this->assignStudentProfiles($students);

        foreach ($sessions as $session) {
            // Skip future sessions
            if ($this->shouldSkipAssessment($session)) continue;

            foreach ($session->terms as $term) {
                $this->generateTermAssessments(
                    $class,
                    $subjects,
                    $students,
                    $studentProfiles,
                    $session,
                    $term,
                    $school
                );
            }
        }
    }


    protected function generateTermAssessments($class, $subjects, $students, $profiles, $session, $term, $school): void
    {
        // Get assessment types for the school
        $assessmentTypes = AssessmentType::where('school_id', $school->id)->get();

        foreach ($subjects as $subject) {
            foreach ($assessmentTypes as $type) {
                // Create subject assessment record
                $assessment = SubjectAssessment::create([
                    'school_id' => $school->id,
                    'subject_id' => $subject->id,
                    'class_room_id' => $class->id,
                    'academic_session_id' => $session->id,
                    'term_id' => $term->id,
                    'assessment_type_id' => $type->id,
                    'title' => "{$type->name} - {$subject->name}",
                    'assessment_date' => $this->generateAssessmentDate($term, $type),
                    'is_published' => true,
                    'published_at' => now(),
                    'created_by' => 1
                ]);

                // Generate grades for each student
                foreach ($students as $student) {
                    $this->generateStudentGrade(
                        $student,
                        $assessment,
                        $profiles[$student->id],
                        $subject,
                        $type
                    );
                }
            }
        }
    }


    protected function assignStudentProfiles($students): array
    {
        $profiles = [];
        $totalStudents = $students->count();

        // Clone students collection to avoid modifying original
        $remainingStudents = $students->shuffle();

        // Assign profiles based on weights
        foreach ($this->performanceProfiles as $profile => $data) {
            $count = (int)ceil($totalStudents * ($data['weight'] / 100));

            for ($i = 0; $i < $count && $remainingStudents->isNotEmpty(); $i++) {
                $student = $remainingStudents->shift();
                $profiles[$student->id] = $profile;
            }
        }

        // Assign remaining students to 'average' profile
        foreach ($remainingStudents as $student) {
            $profiles[$student->id] = 'average';
        }

        return $profiles;
    }

    protected function shouldSkipAssessment($session): bool
    {
        return Carbon::parse($session->start_date)->isFuture();
    }
}
