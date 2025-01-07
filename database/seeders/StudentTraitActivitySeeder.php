<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Staff;
use App\Models\School;
use App\Models\Student;
use App\Models\ActivityType;
use App\Models\StudentGrade;
use App\Helpers\CommentOptions;
use App\Models\BehavioralTrait;
use Illuminate\Database\Seeder;
use App\Models\StudentTermTrait;
use App\Models\StudentTermComment;
use App\Models\StudentTermActivity;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class StudentTraitActivitySeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('slug', 'khalil-integrated-academy')->first();
        $sessions = $school->academicSessions()->get();
        $students = Student::where('school_id', $school->id)->get();

        // Get default traits and activities
        $traits = BehavioralTrait::where('school_id', $school->id)
            ->where('is_default', true)
            ->get();

        $activities = ActivityType::where('school_id', $school->id)
            ->where('is_default', true)
            ->get();

        // Get teachers with their users
        $teachers = Staff::where('school_id', $school->id)
            ->whereHas('designation', function ($query) {
                $query->where('name', 'Teacher');
            })
            ->whereHas('user')  // Ensure staff has associated user
            ->with('user')
            ->get();

        Log::info('teacher found: ' . $teachers);
        if ($teachers->isEmpty()) {
            throw new \Exception('No teachers found for school');
        }

        // Get principal with user
        $principal = Staff::where('school_id', $school->id)
            ->whereHas('designation', function ($query) {
                $query->where('name', 'Principal');
            })
            ->whereHas('user')  // Ensure staff has associated user
            ->with('user')
            ->first();

        if (!$principal || !$principal->user) {
            throw new \Exception('No principal with user account found for school');
        }

        foreach ($sessions as $session) {
            foreach ($session->terms as $term) {
                foreach ($students as $student) {
                    // Generate behavioral traits
                    $traitScores = []; // To track average trait score
                    foreach ($traits as $trait) {
                        $rating = $this->generateRating();
                        $traitScores[] = $rating;

                        StudentTermTrait::firstOrCreate(
                            [
                                'student_id' => $student->id,
                                'academic_session_id' => $session->id,
                                'term_id' => $term->id,
                                'behavioral_trait_id' => $trait->id,
                            ],
                            [
                                'school_id' => $school->id,
                                'rating' => $rating,
                                'remark' => $this->generateRemark($trait->name, $rating),
                                'recorded_by' => 1
                            ]
                        );
                    }

                    // Generate activities (random 3-5 activities per student)
                    $selectedActivities = $activities->random(rand(3, 5));
                    $activityScores = []; // To track average activity score
                    foreach ($selectedActivities as $activity) {
                        $rating = $this->generateRating();
                        $activityScores[] = $rating;

                        StudentTermActivity::firstOrCreate(
                            [
                                'student_id' => $student->id,
                                'academic_session_id' => $session->id,
                                'term_id' => $term->id,
                                'activity_type_id' => $activity->id,
                            ],
                            [
                                'school_id' => $school->id,
                                'rating' => $rating,
                                'remark' => $this->generateRemark($activity->name, $rating),
                                'recorded_by' => 1
                            ]
                        );
                    }

                    // Calculate overall performance for comments
                    $avgScore = StudentGrade::where([
                        'student_id' => $student->id,
                        'academic_session_id' => $session->id,
                        'term_id' => $term->id,
                    ])->avg('score') ?? 60; // Default to 60 if no grades

                    $avgBehavior = collect($traitScores)->avg() ?? 3;
                    $avgActivity = collect($activityScores)->avg() ?? 3;

                    // Weight the scores (60% academics, 20% behavior, 20% activities)
                    $overallScore = ($avgScore * 0.6) + ($avgBehavior * 20 * 0.2) + ($avgActivity * 20 * 0.2);
                    $commentCategory = $this->getCommentCategory($overallScore);

                    // Randomly select a teacher that has a user account
                    $teacher = $teachers->random();

                    // Create or update term comments
                    StudentTermComment::firstOrCreate(
                        [
                            'student_id' => $student->id,
                            'academic_session_id' => $session->id,
                            'term_id' => $term->id,
                        ],
                        [
                            'school_id' => $school->id,
                            'class_teacher_comment' => $this->getRandomComment(
                                CommentOptions::getTeacherCommentsByCategory($commentCategory)
                            ),
                            'class_teacher_id' => $teacher->user->id,  // Use the teacher's user_id
                            'principal_comment' => $this->getRandomComment(
                                CommentOptions::getPrincipalCommentsByCategory($commentCategory)
                            ),
                            'principal_id' => $principal->user->id  // Use the principal's user_id
                        ]
                    );
                }
            }
        }
    }

    protected function generateRating(): int
    {
        // Weight towards better ratings
        $weights = [
            5 => 35,  // 35% chance
            4 => 40,  // 40% chance
            3 => 15,  // 15% chance
            2 => 7,   // 7% chance
            1 => 3    // 3% chance
        ];

        return $this->weightedRandom($weights);
    }

    protected function generateRemark($name, $rating): string
    {
        $remarkTemplates = [
            5 => [
                'Demonstrates exceptional ability in %s',
                'Shows outstanding performance in %s',
                'Excellent engagement with %s',
                'Exceptional dedication to %s'
            ],
            4 => [
                'Shows very good progress in %s',
                'Consistently strong performance in %s',
                'Very good participation in %s',
                'Demonstrates strong ability in %s'
            ],
            3 => [
                'Shows steady progress in %s',
                'Good participation in %s',
                'Demonstrates adequate understanding of %s',
                'Regular engagement with %s'
            ],
            2 => [
                'Needs more practice with %s',
                'Shows basic understanding of %s',
                'Requires more engagement in %s',
                'Developing skills in %s'
            ],
            1 => [
                'Needs significant improvement in %s',
                'Requires additional support with %s',
                'Limited engagement in %s',
                'Struggles with %s'
            ]
        ];

        $templates = $remarkTemplates[$rating];
        return sprintf($templates[array_rand($templates)], $name);
    }

    protected function getCommentCategory($score): string
    {
        if ($score >= 80) return 'excellent';
        if ($score >= 70) return 'very_good';
        if ($score >= 60) return 'good';
        if ($score >= 50) return 'average';
        return 'needs_improvement';
    }

    protected function weightedRandom(array $weights): int
    {
        $rand = rand(1, array_sum($weights));
        $current = 0;

        foreach ($weights as $value => $weight) {
            $current += $weight;
            if ($rand <= $current) {
                return $value;
            }
        }

        return array_key_first($weights);
    }

    protected function getRandomComment(array $comments): string
    {
        return $comments[array_rand($comments)];
    }
}
