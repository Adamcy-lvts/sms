<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PlansTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        // Clear the existing plans to avoid duplicates
        DB::table('plans')->delete();

        // Basic Plan
        DB::table('plans')->insert([
            'name' => 'Basic',
            'price' => 10000,
            'description' => 'Access to essential school management features including student enrollment, admissions processing, payments management, classroom management, and basic school analytics.',
            'duration' => 30,
            'features' => json_encode([
                'Student Enrollment',
                'Admissions Processing',
                'Payments Management',
                'Classroom Management',
                'Academic Session Management',
                'Term Management',
                'Exam Administration',
                'Gradebook Management',
                'Subjects Management',
                'Report Card Generation',
                'Basic School Analytics',
                'Up to 5 Staff Accounts'
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Standard Plan
        DB::table('plans')->insert([
            'name' => 'Standard',
            'price' => 25000,
            'description' => 'Includes all Basic features plus advanced analytics and reporting, staff and teacher management, role-based access control, library management, and student portal access.',
            'duration' => 30,
            'features' => json_encode([
                'All Basic Features',
                'Advanced Analytics & Reporting',
                'Staff Management',
                'Teacher Profiles Management',
                'Role-Based Access Control',
                'Receipt & Invoice Generation',
                'Attendance Tracking',
                'Library Management',
                'Homework Management',
                'Student Portal Access',
                'Student Performance Analytics',
                'Up to 20 Staff Accounts'
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Premium Plan
        DB::table('plans')->insert([
            'name' => 'Premium',
            'price' => 50000,
            'description' => 'Includes all Standard features plus computer-based test integration, real-time data analytics, custom feature requests, enhanced security protocols, and student and parent portal access.',
            'duration' => 30,
            'features' => json_encode([
                'All Standard Features',
                'Computer-Based Test Integration',
                'Real-Time Data Analytics',
                'Custom Feature Requests',
                'Enhanced Security Protocols',
                'Student & Parent Portal Access',
                'Unlimited Staff Accounts'
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
