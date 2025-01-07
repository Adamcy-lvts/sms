<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SetupSchoolCommand extends Command
{
    // Command signature with optional --fresh flag to truncate tables
    protected $signature = 'school:setup {--fresh : Truncate tables before seeding}';

    // Command description
    protected $description = 'Run base seeders to setup school and academic periods';

    // List of tables that will be truncated if --fresh option is used
    protected $tablesToTruncate = [
        'statuses',
        'users',
        'plans',         // Add this line
        'schools',
        'academic_sessions',
        'terms',
        'school_calendar_events',
        'designations',
        'staff', // Add staff table
        'school_user', // Add pivot table
        'class_rooms',     // Add these
        'students',        // three
        'admissions',      // tables
        'states',      // Add these
        'lgas',        // two tables
        'subjects',    // Add this
        'teachers',                    // Add this
        'subject_teacher',            // Add this
        'class_room_teacher',         // Add this
        'student_grades',        // Add this
        'subject_assessments',   // Add this
        'assessment_types',      // Add this
        'grading_scales',        // Add this
        'report_templates',      // Add this line
        'attendance_records',    // Add this line
        'attendance_summaries',  // Add this line
        'payment_methods',       // Add this
        'payment_types',         // payment-related
        'payments',              // Add these
        'payment_items',         // payment-related
        'payment_histories',     // tables
        'activity_types',        // Add this
        'behavioral_traits',     // Add these
        'student_term_traits',   // Add these
        'student_term_activities', // activity-related
        'student_term_comments',   // tables
        'roles',                   // Add this
        'permissions',             // Add this
        'role_has_permissions',    // Add this
        'model_has_roles',         // Add this
        'model_has_permissions',    // Add this
    ];

    protected $seeders = [
        'StatusTableSeeder',
        'UserTableSeeder',
        'PlansTableSeeder',    // Add this line
        // 'SchoolTableSeeder',
        // 'SessionAndTermSeeder',
        'StateTableSeeder',
        'LgaTableSeeder',
        'ShieldSeeder',
        // 'ClassAndStudentSeeder',       // Make sure this runs first
        // 'SubjectTableSeeder',
        // 'SubjectTeacherSeeder',        // Then this
        // 'StaffSeeder',
        // 'StudentAssessmentSeeder',     // Then assessments
        // 'GradeScaleSeeder',
        // 'ReportTemplatesSeeder',
        // 'AttendanceSeeder',      // Add this line
        // 'PaymentMethodTableSeeder',   // Add this
        // 'PaymentTypeSeeder',     // Add this
        // 'PaymentSeeder',           // Add this
        // 'ActivityTypeSeeder',          // Add this
        // 'BehavioralTraitSeeder',   // Add these
        // 'StudentTraitActivitySeeder', // Add this
        // 'SmsPanelRolesAndPermissionsSeeder',
    ];

    public function handle()
    {
        try {
            // Check if --fresh flag is provided
            if ($this->option('fresh')) {
                if ($this->confirm('Are you sure you want to truncate all tables?')) {
                    $this->truncateTables();
                }
            }

            // Start database transaction
            // DB::beginTransaction();
            Log::info('Starting school setup process');

            foreach ($this->seeders as $seeder) {
                try {
                    Log::info("Running seeder: {$seeder}");
                    $this->call('db:seed', ['--class' => $seeder]);
                } catch (\Exception $e) {
                    Log::error("Failed to run seeder: {$seeder}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e;
                }
            }

            // Commit transaction if all seeders run successfully
            // DB::commit();
            Log::info('School setup completed successfully');
            $this->info('School setup completed successfully!');
        } catch (\Exception $e) {
            // Rollback transaction and log error if any seeder fails
            DB::rollBack();
            Log::error('School setup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error('Setup failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Truncate all specified tables
     */
    protected function truncateTables()
    {
        try {
            // Disable foreign key checks before truncating
            Schema::disableForeignKeyConstraints();
            Log::info('Starting table truncation');

            foreach ($this->tablesToTruncate as $table) {
                try {
                    DB::table($table)->truncate();
                    Log::info("Truncated table: {$table}");
                    $this->info("Truncated table: {$table}");
                } catch (\Exception $e) {
                    Log::error("Failed to truncate table: {$table}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e;
                }
            }

            // Re-enable foreign key checks
            Schema::enableForeignKeyConstraints();
            Log::info('All tables truncated successfully');
            $this->info('All tables truncated successfully');
        } catch (\Exception $e) {
            Log::error('Table truncation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
