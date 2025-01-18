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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentAssessmentSeeder extends Seeder
{
    protected $performanceProfiles = [
        'excellent' => ['weight' => 15, 'range' => [85, 100]],
        'good' => ['weight' => 30, 'range' => [70, 84]],
        'average' => ['weight' => 35, 'range' => [55, 69]],
        'poor' => ['weight' => 20, 'range' => [40, 54]]
    ];

    public function run(): void
    {
        try {
            // Disable model events temporarily
            \Illuminate\Database\Eloquent\Model::unsetEventDispatcher();
            
            DB::beginTransaction();

            $school = School::where('slug', 'khalil-integrated-academy')->firstOrFail();
            // $sessions = AcademicSession::where('school_id', $school->id)->with('terms')->get();
            $sessions = AcademicSession::where('school_id', $school->id)->with(['terms'])->orderBy('start_date')->get();
            $subjects = Subject::where('school_id', $school->id)->where('is_active', true)->get();
            $assessmentTypes = AssessmentType::where('school_id', $school->id)->get();

            $classes = ClassRoom::where('school_id', $school->id)
                ->with(['students' => function ($query) {
                    $query->where('status_id', Status::where(['type' => 'student', 'name' => 'active'])->first()?->id);
                }])
                ->get();

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


            foreach ($sessions as $session) {
                // Skip if future session
                if (Carbon::parse($session->start_date)->isFuture()) {
                    return;
                }

                // Skip if term starts in future
                if (Carbon::parse($session->terms->first()->start_date)->isFuture()) {
                    continue;
                }
    
                foreach ($session->terms as $term) {
                    foreach ($classes as $class) {
                        foreach ($class->students as $student) {
                            foreach ($subjects as $subject) {
                                $this->generateStudentGrades(
                                    $school,
                                    $student,
                                    $subject,
                                    $class,
                                    $session,
                                    $term,
                                    $assessmentTypes
                                );
                            }
                        }
                    }
                }
            }

            DB::commit();
            
            // Re-enable model events
            \Illuminate\Database\Eloquent\Model::setEventDispatcher(app('events'));
            
            Log::info('Successfully seeded student grades');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to seed student grades: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function generateStudentGrades($school, $student, $subject, $class, $session, $term, $assessmentTypes): void
    {
        $profile = $this->getStudentProfile($student);

        foreach ($assessmentTypes as $type) {
            // Generate score based on student profile and assessment type
            $score = $this->generateScore($profile, $type->max_score);

            // Create grade record
            StudentGrade::create([
                'school_id' => $school->id,
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'class_room_id' => $class->id,
                'academic_session_id' => $session->id,
                'term_id' => $term->id,
                'assessment_type_id' => $type->id,
                'score' => $score,
                'assessment_date' => $this->generateAssessmentDate($term, $type),
                'recorded_by' => 1,
                'graded_at' => now(),
            ]);
        }
    }

    protected function generateScore($profile, $maxScore): float
    {
        $range = $this->performanceProfiles[$profile]['range'];
        $percentage = rand($range[0], $range[1]) / 100;
        return round($maxScore * $percentage, 2);
    }

    protected function getStudentProfile($student): string
    {
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

    protected function generateAssessmentDate($term, $type): Carbon
    {
        $termStart = Carbon::parse($term->start_date);
        $termEnd = Carbon::parse($term->end_date);
        $termMiddle = $termStart->copy()->addDays($termStart->diffInDays($termEnd) / 2);

        return match ($type->code) {
            'CA1' => $termStart->copy()->addDays(rand(14, 21)),
            'CA2' => $termMiddle->copy()->subDays(rand(7, 14)),
            'CA3' => $termMiddle->copy()->addDays(rand(7, 14)),
            'EXAM' => $termEnd->copy()->subDays(rand(7, 14)),
            default => $termMiddle,
        };
    }
}
