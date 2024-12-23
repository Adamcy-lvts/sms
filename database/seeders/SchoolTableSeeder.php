<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Staff;
use App\Models\School;
use App\Models\Status;
use App\Models\Designation;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SchoolTableSeeder extends Seeder
{
    // Array of school data for seeding
    protected $schools = [
        [
            'name' => 'Kings Private School',
            'email' => 'kings@mail.com',
            'address' => 'Lagos Street, Maiduguri City',
            'phone' => '09012345678',
            'admin' => [
                'first_name' => 'Sadik',
                'last_name' => 'Ahmed',
            ]
        ],
        [
            'name' => 'Khalil Integrated Academy',
            'name_ar' => 'أكاديمية خليل المتكاملة',
            'email' => 'kia@mail.com',
            'address' => 'Lagos Street, Maiduguri City',
            'phone' => '08032145678',
            'admin' => [
                'first_name' => 'Abba',
                'last_name' => 'Mohammed',
            ]
        ],
        [
            'name' => 'Namu Model School',
            'email' => 'namu@mail.com',
            'address' => '123 Education Avenue, Maiduguri',
            'phone' => '07012345678',
            'admin' => [
                'first_name' => 'Daniel',
                'last_name' => 'Samuel',
            ]
        ],
        [
            'name' => 'Manba\'ul Hikmah Academy',
            'name_ar' => 'منبع الحكمة أكاديمية',
            'email' => 'excellence@mail.com',
            'address' => '45 Knowledge Way, Maiduguri',
            'phone' => '08123456789',
            'admin' => [
                'first_name' => 'Alamin',
                'last_name' => 'Abdullah',
            ]
        ],
        [
            'name' => 'Al-Ansar Academy maiduguri',
            'name_ar' => 'أكاديمية الأنصار ميدوغوري',
            'email' => 'al-ansar@mail.com',
            'address' => '78 Success Road, Maiduguri',
            'phone' => '09087654321',
            'admin' => [
                'first_name' => 'Mohammed',
                'last_name' => 'Ibrahim',
            ]
        ],

    ];

    protected $staffSalaries = [
        'Principal' => 300000,
        'Vice Principal' => 250000,
        'Head Teacher' => 220000,
        'Deputy Head Teacher' => 200000,
        'Head of Department' => 180000,
        'Teacher' => 150000,
        'Accountant' => 180000,
        'Bursar' => 170000,
        'Secretary' => 120000,
        'Clerk' => 100000,
        'Librarian' => 120000,
        'Security Guard' => 80000,
        'Cleaner' => 70000,
        'Driver' => 90000,
        'Cook' => 80000,
        'Gardener' => 70000
    ];

    public function run(): void
    {
        if (School::count() > 0) return;
       
        $activeStatus = Status::where('name', 'active')->first();

        foreach ($this->schools as $schoolData) {
            DB::transaction(function () use ($schoolData, $activeStatus) {
                $school = School::create([
                    'name' => $schoolData['name'],
                    'slug' => Str::slug($schoolData['name']),
                    'name_ar' => $schoolData['name_ar'] ?? null,
                    'email' => $schoolData['email'],
                    'address' => $schoolData['address'],
                    'phone' => $schoolData['phone'],
                    'settings' => json_encode(['theme' => 'default']),
                ]);

                // Create designations for this school
                $this->createDesignations($school);
               
                // Create principal first
                $principal = $this->createPrincipalStaff($school, $schoolData, $activeStatus);

                // Create other admin staff
                // $this->createAdminStaff($school, $activeStatus);
            });
            
        }
    }

    protected function createDesignations($school): void
    {
        $designations = [
            ['name' => 'Principal', 'description' => 'The head of the school'],
            ['name' => 'Vice Principal', 'description' => 'The deputy head of the school'],
            ['name' => 'Head Teacher', 'description' => 'The head of the primary section'],
            ['name' => 'Deputy Head Teacher', 'description' => 'The deputy head of the primary section'],
            ['name' => 'Head of Department', 'description' => 'The head of a department'],
            ['name' => 'Teacher', 'description' => 'A classroom teacher'],
            ['name' => 'Clerk', 'description' => 'A school clerk'],
            ['name' => 'Librarian', 'description' => 'A school librarian'],
            ['name' => 'Security Guard', 'description' => 'A school security guard'],
            ['name' => 'Cleaner', 'description' => 'A school cleaner'],
            ['name' => 'Driver', 'description' => 'A school driver'],
            ['name' => 'Cook', 'description' => 'A school cook'],
            ['name' => 'Gardener', 'description' => 'A school gardener'],
            ['name' => 'Accountant', 'description' => 'A school accountant'],
            ['name' => 'Bursar', 'description' => 'A school bursar'],
            ['name' => 'Secretary', 'description' => 'A school secretary'],
        ];

        foreach ($designations as $designation) {
            Designation::create([
                'name' => $designation['name'],
                'description' => $designation['description'],
                'school_id' => $school->id
            ]);
        }
    }

    protected function createPrincipalStaff($school, $schoolData, $status)
    {
        $designation = Designation::where('school_id', $school->id)
            ->where('name', 'Principal')
            ->first();

        $staff = Staff::create([
            'school_id' => $school->id,
            'designation_id' => $designation->id,
            'first_name' => $schoolData['admin']['first_name'],
            'last_name' => $schoolData['admin']['last_name'],
            'status_id' => $status->id,
            'employee_id' => 'EMP' . str_pad($school->id, 3, '0', STR_PAD_LEFT) . '001',
            'gender' => rand(0, 1) ? 'Male' : 'Female',
            'email' => $schoolData['email'],
            'phone_number' => $schoolData['phone'],
            'salary' => $this->staffSalaries['Principal'],
            'date_of_birth' => now()->subYears(40),
            'hire_date' => now(),
            'address' => rand(1, 100) . ' Teachers Quarters, Maiduguri',
    
        ]);

        $user = User::create([
            'first_name' => $staff->first_name,
            'last_name' => $staff->last_name,
            'email' => $staff->email,
            'password' => Hash::make('password123'),
            'status_id' => $status->id
        ]);

        $staff->user()->associate($user);
        $staff->save();

        $school->members()->attach($user->id);

        return $staff;
    }

    protected function generateEmployeeId($school)
    {
        $lastStaff = Staff::where('school_id', $school->id)->latest('id')->first();
        $number = $lastStaff ? (intval(substr($lastStaff->employee_id, -3)) + 1) : 1;
        return 'EMP' . str_pad($school->id, 3, '0', STR_PAD_LEFT) . str_pad($number, 3, '0', STR_PAD_LEFT);
    }
}

