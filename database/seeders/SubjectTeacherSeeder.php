<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Staff;
use App\Models\School;
use App\Models\Status;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\ClassRoom;
use App\Models\Designation;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubjectTeacherSeeder extends Seeder
{
    protected $teacherNames = [
        [
            'first_name' => 'Abdullahi',
            'last_name' => 'Ibrahim',
            'specialization' => 'Arabic and Islamic Studies',
            'experience' => '10 years teaching Islamic subjects'
        ],
        [
            'first_name' => 'Fatima',
            'last_name' => 'Ahmad',
            'specialization' => 'Quran and Tajweed',
            'experience' => '8 years in Quranic studies'
        ],
        // Regular subject teachers
        [
            'first_name' => 'Musa',
            'last_name' => 'Ibrahim',
            'specialization' => 'Mathematics and Sciences',
            'experience' => '8 years in mathematics and sciences'
        ],
        [
            'first_name' => 'Grace',
            'last_name' => 'Okafor',
            'specialization' => 'Languages',
            'experience' => '6 years in English language'
        ],
        [
            'first_name' => 'Ahmed',
            'last_name' => 'Suleiman',
            'specialization' => 'Sciences',
            'experience' => '5 years in sciences'
        ],
        // Add more teacher profiles as needed
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $school = School::findOrFail(2); // Only get Khalil Integrated Academy
            $activeStatus = Status::where('type', 'staff')
                ->where('name', 'active')
                ->firstOrFail();

                $designations = [
                    ['name' => 'Principal', 'description' => 'The head of the school', 'school_id' => $school->id],
                    ['name' => 'Vice Principal', 'description' => 'The deputy head of the school','school_id' => $school->id],
                    ['name' => 'Head Teacher', 'description' => 'The head of the primary section', 'school_id' => $school->id],
                    ['name' => 'Deputy Head Teacher', 'description' => 'The deputy head of the primary section', 'school_id' => $school->id],
                    ['name' => 'Head of Department', 'description' => 'The head of a department', 'school_id' => $school->id],
                    ['name' => 'Teacher', 'description' => 'A classroom teacher', 'school_id' => $school->id],
                    ['name' => 'Clerk', 'description' => 'A school clerk', 'school_id' => $school->id],
                    ['name' => 'Librarian', 'description' => 'A school librarian', 'school_id' => $school->id],
                    ['name' => 'Security Guard', 'description' => 'A school security guard', 'school_id' => $school->id],
                    ['name' => 'Cleaner', 'description' => 'A school cleaner', 'school_id' => $school->id],
                    ['name' => 'Driver', 'description' => 'A school driver', 'school_id' => $school->id],
                    ['name' => 'Cook', 'description' => 'A school cook', 'school_id' => $school->id],
                    ['name' => 'Gardener', 'description' => 'A school gardener', 'school_id' => $school->id],
                    ['name' => 'Accountant', 'description' => 'A school accountant', 'school_id' => $school->id],
                    ['name' => 'Bursar', 'description' => 'A school bursar', 'school_id' => $school->id],
                    ['name' => 'Secretary', 'description' => 'A school secretary', 'school_id' => $school->id],
                  
                ];
            // Create teaching designation
            $teacherDesignation = Designation::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'name' => 'Teacher'
                ],
                ['description' => 'Class Teacher']
            );

            // Get all subjects for this school
            $subjects = Subject::where('school_id', $school->id)->get();
            if ($subjects->isEmpty()) {
                Log::warning("No subjects found for school: {$school->name}");
                return;
            }

            $classRooms = ClassRoom::where('school_id', $school->id)->get();
            if ($classRooms->isEmpty()) {
                Log::warning("No classrooms found for school: {$school->name}");
                return;
            }

            // Calculate required teachers (adjusted for smaller class count)
            $requiredTeachers = ceil(max(
                $subjects->count() / 3, // Each teacher handles ~3 subjects
                $classRooms->count() / 3  // Each teacher handles ~3 classes
            ));

            // Create teachers
            for ($i = 0; $i < $requiredTeachers; $i++) {
                // Randomly select a teacher profile
                $teacherProfile = $this->teacherNames[array_rand($this->teacherNames)];

                // Create staff record
                $staff = Staff::create([
                    'school_id' => $school->id,
                    'designation_id' => $teacherDesignation->id,
                    'status_id' => $activeStatus->id,
                    'first_name' => $teacherProfile['first_name'],
                    'last_name' => $teacherProfile['last_name'] . ($i > 0 ? " {$i}" : ''), // Add number to prevent duplicate names
                    'gender' => rand(0, 1) ? 'Male' : 'Female',
                    'date_of_birth' => Carbon::now()->subYears(rand(28, 45)),
                    'phone_number' => '080' . rand(10000000, 99999999),
                    'email' => Str::slug($teacherProfile['first_name'] . $teacherProfile['last_name']) . $i . '@' . $school->slug . '.com',
                    'address' => rand(1, 100) . ' Teachers Quarters, Maiduguri',
                    'hire_date' => Carbon::now()->subYears(rand(1, 5)),
                    'employee_id' => 'TCH' . microtime(true) . rand(100, 999) . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                    'salary' => rand(50000, 150000), // Random salary between 50k and 150k
                ]);

                // Create teacher record
                $teacher = Teacher::create([
                    'school_id' => $school->id,
                    'staff_id' => $staff->id,
                    'specialization' => $teacherProfile['specialization'],
                    'teaching_experience' => $teacherProfile['experience'],
                ]);

                // Assign subjects to teacher - modified to handle edge cases
                $numSubjects = min(rand(2, 3), $subjects->count());
                $teacherSubjects = $subjects->random($numSubjects);
                $teacher->subjects()->attach($teacherSubjects->pluck('id'));

                // Assign classes to teacher - modified to handle edge cases
                $numClasses = min(rand(2, 4), $classRooms->count());
                $teacherClasses = $classRooms->random($numClasses);
                $teacher->classRooms()->attach($teacherClasses->pluck('id'));
            }

            Log::info("Created {$requiredTeachers} teachers for school: {$school->name}");
        });
    }
}
