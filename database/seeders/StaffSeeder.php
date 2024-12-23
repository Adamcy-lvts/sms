<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\User;
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
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    // Define teacher specializations with their corresponding subjects
    protected $teacherSpecializations = [
        'Mathematics and Sciences' => [
            'subjects' => ['Mathematics', 'Basic Science'],
            'classes' => 4, // Number of classes to assign
        ],
        'Languages' => [
            'subjects' => ['English Language'],
            'classes' => 3,
        ],
        'Social Sciences' => [
            'subjects' => ['Social Studies', 'Civic Education'],
            'classes' => 3,
        ],
        'Islamic Studies' => [
            'subjects' => ['Al Quran (القرآن)', 'Hadith (حديث)', 'Fiqh (فقه)'],
            'classes' => 3,
        ],
        'Arts and Creativity' => [
            'subjects' => ['Creative Arts', 'Computer Studies'],
            'classes' => 2,
        ],
        'Vocational Studies' => [
            'subjects' => ['Agricultural Science', 'Home Economics'],
            'classes' => 2,
        ]
    ];

    // Define teaching staff profiles
    protected $teachingStaff = [
        [
            'first_name' => 'Musa',
            'last_name' => 'Ibrahim',
            'specialization' => 'Mathematics and Sciences',
            'experience' => '8 years teaching mathematics and sciences',
            'salary_range' => [100000, 150000]
        ],
        [
            'first_name' => 'Fatima',
            'last_name' => 'Usman',
            'specialization' => 'Languages',
            'experience' => '6 years teaching languages',
            'salary_range' => [90000, 130000]
        ],
        [
            'first_name' => 'Ahmed',
            'last_name' => 'Suleiman',
            'specialization' => 'Social Sciences',
            'experience' => '5 years teaching social sciences',
            'salary_range' => [85000, 120000]
        ],
        [
            'first_name' => 'Abdullahi',
            'last_name' => 'Muhammad',
            'specialization' => 'Islamic Studies',
            'experience' => '10 years teaching Islamic studies',
            'salary_range' => [110000, 160000]
        ],
        [
            'first_name' => 'Zainab',
            'last_name' => 'Hassan',
            'specialization' => 'Arts and Creativity',
            'experience' => '4 years teaching arts',
            'salary_range' => [80000, 110000]
        ],
        [
            'first_name' => 'Aisha',
            'last_name' => 'Yusuf',
            'specialization' => 'Vocational Studies',
            'experience' => '7 years teaching vocational subjects',
            'salary_range' => [95000, 140000]
        ],
    ];


    protected $nonTeachingStaff = [
        [
            'designation' => 'Principal',
            'first_name' => 'Ibrahim',
            'last_name' => 'Muhammad',
            'experience' => '15 years in education administration',
            'salary_range' => [250000, 300000],
            'gender' => 'Male'
        ],
        [
            'designation' => 'Vice Principal',
            'first_name' => 'Aisha',
            'last_name' => 'Abdullahi',
            'experience' => '12 years in school administration',
            'salary_range' => [200000, 250000],
            'gender' => 'Female'
        ],
        [
            'designation' => 'Accountant',
            'first_name' => 'Yusuf',
            'last_name' => 'Aliyu',
            'experience' => '10 years in accounting',
            'salary_range' => [150000, 180000],
            'gender' => 'Male'
        ],
        [
            'designation' => 'Secretary',
            'first_name' => 'Mary',
            'last_name' => 'James',
            'experience' => '5 years office administration',
            'salary_range' => [80000, 100000],
            'gender' => 'Female'
        ],
        [
            'designation' => 'Librarian',
            'first_name' => 'Grace',
            'last_name' => 'Peter',
            'experience' => '4 years library management',
            'salary_range' => [70000, 90000],
            'gender' => 'Female'
        ]
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $school = School::where('slug', 'khalil-integrated-academy')->first();

            // Get active statuses for both staff and users
            $activeStaffStatus = Status::where('type', 'staff')
                ->where('name', 'active')
                ->first();

            $activeUserStatus = Status::where('type', 'user')
                ->where('name', 'active')
                ->first();

            // Get teaching designation
            $teacherDesignation = Designation::where('school_id', $school->id)
                ->where('name', 'Teacher')
                ->first();

            $principalDesignation = Designation::where('school_id', $school->id)
                ->where('name', 'Principal')
                ->first();

            // Get subjects and classes
            $allSubjects = Subject::where('school_id', $school->id)->get();
            $allClasses = ClassRoom::where('school_id', $school->id)->get();

            // Create teaching staff
            foreach ($this->teachingStaff as $index => $profile) {
                // Create user account first
                $user = User::create([
                    'first_name' => $profile['first_name'],
                    'last_name' => $profile['last_name'],
                    'email' => Str::slug($profile['first_name'] . $profile['last_name']) . '@' . $school->slug . '.com',
                    'status_id' => $activeUserStatus->id,
                    'password' => Hash::make('password'), // Default password
                ]);

                // Attach school to user
                $user->schools()->attach($school->id);

                // Create staff record with user_id
                $staff = Staff::create([
                    'school_id' => $school->id,
                    'user_id' => $user->id, // Link to user account
                    'designation_id' => $teacherDesignation->id,
                    'status_id' => $activeStaffStatus->id,
                    'first_name' => $profile['first_name'],
                    'last_name' => $profile['last_name'],
                    'gender' => rand(0, 1) ? 'Male' : 'Female',
                    'date_of_birth' => Carbon::now()->subYears(rand(28, 45)),
                    'phone_number' => '080' . rand(10000000, 99999999),
                    'email' => $user->email, // Use same email as user account
                    'address' => rand(1, 100) . ' Teachers Quarters, Maiduguri',
                    'hire_date' => Carbon::now()->subYears(rand(1, 5)),
                    'employee_id' => 'TCH' . date('Y') . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                    'salary' => rand($profile['salary_range'][0], $profile['salary_range'][1]),
                ]);

                // Create teacher record and assign subjects
                $teacher = $this->createTeacherRecord($school, $staff, $profile);
                $this->assignSubjectsAndClasses($teacher, $profile['specialization'], $allSubjects, $allClasses);
            }

            // Non-teaching staff (only create user accounts for principals)
            foreach ($this->nonTeachingStaff as $index => $profile) {
                $user = null;
                if ($profile['designation'] === 'Principal') {
                    $user = User::create([
                        'first_name' => $profile['first_name'],
                        'last_name' => $profile['last_name'],
                        'email' => Str::slug($profile['first_name'] . $profile['last_name']) . '@' . $school->slug . '.com',
                        'status_id' => $activeUserStatus->id,
                        'password' => Hash::make('password'),
                    ]);
                    $user->schools()->attach($school->id);
                }

                $designation = Designation::where('school_id', $school->id)
                    ->where('name', $profile['designation'])
                    ->first();

                // Create staff record with all required fields
                Staff::create([
                    'school_id' => $school->id,
                    'user_id' => $user?->id,
                    'designation_id' => $designation->id,
                    'status_id' => $activeStaffStatus->id, // Added missing status_id
                    'first_name' => $profile['first_name'],
                    'last_name' => $profile['last_name'],
                    'gender' => $profile['gender'],
                    'date_of_birth' => Carbon::now()->subYears(rand(35, 55)),
                    'phone_number' => '080' . rand(10000000, 99999999),
                    'email' => Str::slug($profile['first_name'] . $profile['last_name']) . '@' . $school->slug . '.com',
                    'address' => rand(1, 100) . ' Staff Quarters, Maiduguri',
                    'hire_date' => Carbon::now()->subYears(rand(1, 5)),
                    'employee_id' => Str::upper(substr($profile['designation'], 0, 3)) . date('Y') . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                    'salary' => rand($profile['salary_range'][0], $profile['salary_range'][1]),
                ]);
            }

            // Log summary
            Log::info("Created staff records with user accounts where needed");
        });
    }

    protected function createStaffRecord($school, $designation, $status, $profile, $index)
    {
        return Staff::create([
            'school_id' => $school->id,
            'designation_id' => $designation->id,
            'status_id' => $status->id,
            'first_name' => $profile['first_name'],
            'last_name' => $profile['last_name'],
            'gender' => rand(0, 1) ? 'Male' : 'Female',
            'date_of_birth' => Carbon::now()->subYears(rand(28, 45)),
            'phone_number' => '080' . rand(10000000, 99999999),
            'email' => Str::slug($profile['first_name'] . $profile['last_name']) . '@' . $school->slug . '.com',
            'address' => rand(1, 100) . ' Teachers Quarters, Maiduguri',
            'hire_date' => Carbon::now()->subYears(rand(1, 5)),
            'employee_id' => 'TCH' . date('Y') . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
            'salary' => rand($profile['salary_range'][0], $profile['salary_range'][1]),
        ]);
    }

    protected function createTeacherRecord($school, $staff, $profile)
    {
        return Teacher::create([
            'school_id' => $school->id,
            'staff_id' => $staff->id,
            'specialization' => $profile['specialization'],
            'teaching_experience' => $profile['experience'],
        ]);
    }

    protected function assignSubjectsAndClasses($teacher, $specialization, $allSubjects, $allClasses)
    {
        // Get specialization configuration
        $config = $this->teacherSpecializations[$specialization];

        // Find and assign subjects
        $subjectsToAssign = $allSubjects->filter(function ($subject) use ($config) {
            return in_array($subject->name, $config['subjects']);
        });
        $teacher->subjects()->attach($subjectsToAssign->pluck('id'));

        // Assign random classes based on configuration
        $classesToAssign = $allClasses->random(min($config['classes'], $allClasses->count()));
        $teacher->classRooms()->attach($classesToAssign->pluck('id'));
    }
}
