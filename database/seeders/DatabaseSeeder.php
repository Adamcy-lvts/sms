<?php

namespace Database\Seeders;

use App\Models\Designation;
use App\Models\User;
use App\Models\Payment;
use App\Models\ReportTemplate;
use App\Models\Staff;
use App\Models\Template;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;



class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Wrap all seeding in a transaction
        DB::transaction(function () {
            // Base configuration and lookup tables
            $this->call([
                StatusTableSeeder::class,           // First: Create statuses
                SchoolTableSeeder::class,        // Schools
                StateTableSeeder::class,         // States lookup
                LgaTableSeeder::class,           // Local Government Areas
                UserTableSeeder::class,          // Base users
                DesignationTableSeeder::class,   // Staff designations
            ]);

            // School setup and configuration
            $this->call([
               
                SessionAndTermSeeder::class,     // Academic sessions/terms
                // ClassRoomTableSeeder::class,     // Classes
                StaffSeeder::class,              // Staff
                GradeScaleSeeder::class,         // Grading scales
                ClassAndStudentSeeder::class,    // Classes and students
                SubjectTableSeeder::class,       // Subjects
                SubjectTeacherSeeder::class,     // Subject-teacher assignments
                TemplateTableSeeder::class,      // Report templates
                ReportTemplatesSeeder::class,     // Report templates
            ]);

            // Academic and behavioral settings
            $this->call([
                ActivityTypeSeeder::class,       // Activity types
                BehavioralTraitSeeder::class,    // Behavioral traits
                HolidaySeeder::class,           // School holidays
            ]);

            // Financial configuration
            $this->call([
                BankTableSeeder::class,          // Banks
                PaymentMethodTableSeeder::class, // Payment methods
                PaymentTypeSeeder::class,        // Payment types
                PaymentTypeSeeder::class,   // Payment type configurations
                ExpenseCategoryItemSeeder::class,
                PlansTableSeeder::class,         // Subscription plans
            ]);

            // Staff and admissions
            $this->call([
                DesignationTableSeeder::class,   // Staff designations
                // AdmissionTableSeeder::class,     // Student admissions
            ]);

            // Academic assessments and student data
            $this->call([
                StudentAssessmentSeeder::class,  // Finally: Create assessments
                AttendanceSeeder::class,         // Student attendance
                StudentTraitActivitySeeder::class, // Student
            ]);

            // Financial transactions
            $this->call([
                PaymentSeeder::class,            // Student payments
            ]);
        });
    }

    /**
     * Get list of critical seeders that should run first
     */
    public static function getCriticalSeeders(): array
    {
        return [
            StateTableSeeder::class,
            LgaTableSeeder::class,
            StatusTableSeeder::class,
            UserTableSeeder::class,
            SchoolTableSeeder::class,
        ];
    }

    /**
     * Get independent seeders that can run in parallel
     */
    public static function getParallelSeeders(): array
    {
        return [
            ActivityTypeSeeder::class,
            BehavioralTraitSeeder::class,
            HolidaySeeder::class,
            BankTableSeeder::class,
            PaymentMethodTableSeeder::class,
            PaymentTypeSeeder::class,
           
            PlansTableSeeder::class,
        ];
    }

    /**
     * Get seeders that depend on other data being present
     */
    public static function getDependentSeeders(): array
    {
        return [
            SessionAndTermSeeder::class,
            ClassRoomTableSeeder::class,
            SubjectTeacherSeeder::class,
            // AdmissionTableSeeder::class,
            StudentAssessmentSeeder::class,
             // Finally, create the financial records in correct order
             PaymentSeeder::class,             // Create student payments first
             ExpenseSeeder::class,             // Create corresponding expenses
        ];
    }

    /**
     * Run seeders for fresh installation
     */
    public function runFreshInstall(): void
    {
        $this->call($this->getCriticalSeeders());
    }

    /**
     * Run seeders for demo data
     */
    public function runDemoData(): void
    {
        $this->call(array_merge(
            $this->getParallelSeeders(),
            $this->getDependentSeeders()
        ));
    }
}


