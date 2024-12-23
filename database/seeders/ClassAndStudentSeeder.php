<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\State;
use App\Models\School;
use App\Models\Status;
use App\Models\Student;
use App\Models\Admission;
use App\Models\ClassRoom;
use Illuminate\Support\Str;
use App\Models\AcademicSession;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class ClassAndStudentSeeder extends Seeder
{
    // Reduce class rooms to bare minimum
    protected $classRooms = [
        'Secondary' => [
            'JSS 1A',
            'JSS 2A',
            'JSS 3A',
            'SSS 1A',
            'SSS 2A',
            'SSS 3A'
        ],
        'Primary' => [
            'Primary 5A',
            'Primary 6A'
        ]
    ];

    protected $namesData = [
        'firstNames' => [
            'Abdullah',
            'Ahmad',
            'Mohammed',
            'Aisha',
            'Fatima',
            'Ibrahim',
            'Omar',
            'Zainab',
            'Hassan',
            'Amina',
            'Yusuf',
            'Maryam',
            'Ali',
            'Khadija',
            'Usman',
            'Safiya',
            'Hamza',
            'Ruqayya',
            'Bilal',
            'Hafsat'
        ],
        'lastNames' => [
            'Mohammed',
            'Ibrahim',
            'Abubakar',
            'Usman',
            'Ali',
            'Abdullah',
            'Ahmad',
            'Hassan',
            'Yusuf',
            'Umar',
            'Suleiman',
            'Idris',
            'Musa',
            'Ismail',
            'Abdulrahman'
        ]
    ];

    protected function determineSessionType(AcademicSession $session): string
    {
        $sessionMap = [
            '2022/2023' => 'historical',
            '2023/2024' => 'previous',
            '2024/2025' => 'current'
        ];

        return $sessionMap[$session->name] ?? 'unknown';
    }

    public function run(): void
    {
        DB::transaction(function () {
            $school = School::findOrFail(2); // Only get Khalil Integrated Academy
            $sessions = AcademicSession::orderBy('start_date')->get();
            
            // Debug log statuses
            $allStatuses = Status::where('type', 'student')->get();
            Log::info('Available student statuses:', $allStatuses->toArray());
            
            $activeStatus = Status::where('name', 'active')->where('type', 'student')->first();
            Log::info("Active Status:", $activeStatus->toArray());
            
            Log::info("Processing school: {$school->name}");
            $classes = $this->createClasses($school);
            
            // Log initial state
            $totalStudents = Student::count();
            $activeStudents = Student::where('status_id', $activeStatus->id)->count();
            Log::info("Initial counts - Total students: {$totalStudents}, Active students: {$activeStudents}");

            foreach ($sessions as $session) {
                $sessionType = $this->determineSessionType($session);
                Log::info("Processing {$sessionType} session: {$session->name}");

                foreach ($classes as $class) {
                    // Log before creating admissions
                    $beforeCount = Student::where([
                        'class_room_id' => $class->id,
                        'status_id' => $activeStatus->id,
                    ])->count();
                    Log::info("Before creating admissions for {$class->name}: {$beforeCount} active students");

                    $this->createSessionClassAdmissions($school, $session, $class, $activeStatus, $sessionType);

                    // Log after creating admissions
                    $afterCount = Student::where([
                        'class_room_id' => $class->id,
                        'status_id' => $activeStatus->id,
                    ])->count();
                    Log::info("After creating admissions for {$class->name}: {$afterCount} active students");
                }

                // Log session summary
                $sessionStudents = Student::whereHas('admission', function($query) use ($session) {
                    $query->where('academic_session_id', $session->id);
                })->count();
                $sessionActiveStudents = Student::whereHas('admission', function($query) use ($session) {
                    $query->where('academic_session_id', $session->id);
                })->where('status_id', $activeStatus->id)->count();
                Log::info("Session {$session->name} summary - Total: {$sessionStudents}, Active: {$sessionActiveStudents}");
            }

            // Log final state
            $finalTotalStudents = Student::count();
            $finalActiveStudents = Student::where('status_id', $activeStatus->id)->count();
            Log::info("Final counts - Total students: {$finalTotalStudents}, Active students: {$finalActiveStudents}");

            // Log class-wise distribution
            foreach ($classes as $class) {
                $classStudents = Student::where('class_room_id', $class->id)->count();
                $classActiveStudents = Student::where([
                    'class_room_id' => $class->id,
                    'status_id' => $activeStatus->id,
                ])->count();
                Log::info("Class {$class->name} - Total: {$classStudents}, Active: {$classActiveStudents}");
            }
        });
    }

    protected function createClasses(School $school): Collection
    {
        $classesArray = [];

        foreach ($this->classRooms as $section => $sectionClasses) {
            foreach ($sectionClasses as $className) {
                $class = ClassRoom::firstOrCreate([
                    'school_id' => $school->id,
                    'name' => $className,
                    'slug' => Str::slug($className),
                    'capacity' => $this->getClassCapacity($className)
                ]);

                $classesArray[] = $class;
            }
        }

        return new Collection($classesArray);
    }

    protected function getClassCapacity(string $className): int
    {
        if (str_contains($className, 'Primary')) {
            return match (true) {
                str_contains($className, 'Primary 1') => 45,
                str_contains($className, 'Primary 2') => 42,
                str_contains($className, 'Primary 3') => 40,
                str_contains($className, 'Primary 4') => 38,
                str_contains($className, 'Primary 5') => 35,
                str_contains($className, 'Primary 6') => 32
            };
        }

        return match (true) {
            str_contains($className, 'JSS 1') => 45,
            str_contains($className, 'JSS 2') => 42,
            str_contains($className, 'JSS 3') => 40,
            str_contains($className, 'SSS 1') => 38,
            str_contains($className, 'SSS 2') => 35,
            str_contains($className, 'SSS 3') => 32
        };
    }

    protected function createSessionClassAdmissions(School $school, AcademicSession $session, ClassRoom $class, Status $status, string $sessionType): void
    {
        $fillRates = match ($sessionType) {
            'historical' => ['min' => 60, 'max' => 70], // Reduced fill rates
            'previous' => ['min' => 50, 'max' => 60],
            'current' => ['min' => 30, 'max' => 40],
            default => ['min' => 30, 'max' => 40]
        };

        $this->createClassAdmissions($school, $session, $class, $status, $fillRates);
    }

    protected function generateNigerianPhoneNumber(): string
    {
        $prefixes = ['0803', '0805', '0806', '0807', '0808', '0809', '0810', '0811', '0812', '0813', '0814', '0815', '0816', '0817', '0818', '0819', '0909', '0908'];
        $prefix = $prefixes[array_rand($prefixes)];
        return $prefix . rand(10000000, 99999999);
    }

    protected function getRandomGuardianName(): string
    {
        $firstName = $this->namesData['firstNames'][array_rand($this->namesData['firstNames'])];
        $lastName = $this->namesData['lastNames'][array_rand($this->namesData['lastNames'])];
        return $firstName . ' ' . $lastName;
    }

    protected function createClassAdmissions(School $school, AcademicSession $session, ClassRoom $class, Status $status, array $fillRates): void
    {
        $capacity = min($class->capacity, 20); // Limit capacity to 20 students max
        $currentCount = Student::where('class_room_id', $class->id)->count();
        Log::info("Current count for {$class->name}: {$currentCount}");
        $remainingCapacity = max(0, $capacity - $currentCount);

        if ($remainingCapacity <= 0) {
            Log::info("Class {$class->name} is at capacity. Skipping admission creation.");
            return;
        }

        $targetCount = min(
            $remainingCapacity,
            (int)ceil($capacity * rand($fillRates['min'], $fillRates['max']) / 100)
        );

        $states = State::with('lgas')->get();

        for ($i = 0; $i < $targetCount; $i++) {
            $firstName = $this->namesData['firstNames'][array_rand($this->namesData['firstNames'])];
            $lastName = $this->namesData['lastNames'][array_rand($this->namesData['lastNames'])];
            $state = $states->random();
            $lga = $state->lgas->random();
            $guardianName = $this->getRandomGuardianName();

            $admission = Admission::create([
                'school_id' => $school->id,
                'academic_session_id' => $session->id,
                'session' => $session->name,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'middle_name' => null,
                'date_of_birth' => $this->generateDateOfBirth($class->name),
                'gender' => rand(0, 1) ? 'Male' : 'Female',
                'address' => fake()->address,
                'phone_number' => $this->generateNigerianPhoneNumber(),
                'email' => strtolower($firstName . '.' . $lastName . '@example.com'),
                'state_id' => $state->id,
                'lga_id' => $lga->id,
                'religion' => $this->getRandomReligion(),
                'blood_group' => $this->getRandomBloodGroup(),
                'genotype' => $this->getRandomGenotype(),
                'admitted_date' => Carbon::parse($session->start_date)->subDays(rand(1, 30)),
                'application_date' => Carbon::parse($session->start_date)->subDays(rand(31, 60)),
                'status_id' => $status->id,
                'admission_number' => $this->generateAdmissionNumber($school, $session),
                'guardian_name' => $guardianName,
                'guardian_relationship' => $this->getRandomRelationship(),
                'guardian_phone_number' => $this->generateNigerianPhoneNumber(),
                'guardian_email' => fake()->email,
                'guardian_address' => fake()->address,
                'emergency_contact_name' => $this->getRandomGuardianName(),
                'emergency_contact_relationship' => $this->getRandomRelationship(),
                'emergency_contact_phone_number' => $this->generateNigerianPhoneNumber(),
                'emergency_contact_email' => fake()->email,
            ]);

            Student::create([
                'school_id' => $school->id,
                'admission_id' => $admission->id,
                'class_room_id' => $class->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'admission_number' => $admission->admission_number,
                'date_of_birth' => $admission->date_of_birth,
                'status_id' => $status->id,
                'created_by' => 3,
            ]);
        }
    }

    protected function generateDateOfBirth(string $className): Carbon
    {
        $baseAge = match (true) {
            str_contains($className, 'Primary 1') => 5,
            str_contains($className, 'Primary 2') => 6,
            str_contains($className, 'Primary 3') => 7,
            str_contains($className, 'Primary 4') => 8,
            str_contains($className, 'Primary 5') => 9,
            str_contains($className, 'Primary 6') => 10,
            str_contains($className, 'JSS 1') => 11,
            str_contains($className, 'JSS 2') => 12,
            str_contains($className, 'JSS 3') => 13,
            str_contains($className, 'SSS 1') => 14,
            str_contains($className, 'SSS 2') => 15,
            str_contains($className, 'SSS 3') => 16,
            default => 5
        };

        return now()->subYears($baseAge)
            ->subMonths(rand(0, 11))
            ->subDays(rand(0, 30));
    }

    protected function generateAdmissionNumber(School $school, AcademicSession $session): string
    {
        $year = Carbon::parse($session->start_date)->format('Y');
        $schoolPrefix = 'KIA';

        // Get the highest sequence number for this school and year
        $lastAdmission = Admission::where('school_id', $school->id)
            ->where('admission_number', 'LIKE', "{$schoolPrefix}/{$year}/%")
            ->orderByRaw('CAST(SUBSTRING_INDEX(admission_number, "/", -1) AS UNSIGNED) DESC')
            ->first();

        $sequence = 1;
        if ($lastAdmission) {
            $parts = explode('/', $lastAdmission->admission_number);
            $sequence = (int)end($parts) + 1;
        }

        $admissionNumber = sprintf('%s/%s/%04d', $schoolPrefix, $year, $sequence);

        // Verify uniqueness and increment if necessary
        while (Admission::where('admission_number', $admissionNumber)->exists()) {
            $sequence++;
            $admissionNumber = sprintf('%s/%s/%04d', $schoolPrefix, $year, $sequence);
        }

        return $admissionNumber;
    }

    protected function getRandomReligion(): string
    {
        return ['Islam', 'Christianity', 'Traditional'][array_rand(['Islam', 'Christianity', 'Traditional'])];
    }

    protected function getRandomBloodGroup(): string
    {
        return ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'][array_rand(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'])];
    }

    protected function getRandomGenotype(): string
    {
        return ['AA', 'AS', 'SS', 'AC'][array_rand(['AA', 'AS', 'SS', 'AC'])];
    }

    protected function getRandomRelationship(): string
    {
        return ['Father', 'Mother', 'Uncle', 'Aunt', 'Guardian'][array_rand(['Father', 'Mother', 'Uncle', 'Aunt', 'Guardian'])];
    }
}
