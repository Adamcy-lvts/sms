<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeaturesTableSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Feature::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $features = [
            // Core Features
            [
                'name' => 'Profile Management',
                'slug' => 'profile_management',
                'is_limitable' => false
            ],
            [
                'name' => 'Role-Based Access Control',
                'slug' => 'rbac',
                'is_limitable' => false
            ],
            [
                'name' => 'Financial Management',
                'slug' => 'financial_management',
                'is_limitable' => false
            ],
            [
                'name' => 'Attendance Tracking',
                'slug' => 'attendance_tracking',
                'is_limitable' => false
            ],
            [
                'name' => 'Report Card Generation',
                'slug' => 'report_card_generation',
                'is_limitable' => false
            ],
            [
                'name' => 'Basic Analytics',
                'slug' => 'basic_analytics',
                'is_limitable' => false
            ],
            // Standard Features
            [
                'name' => 'Admission Management',
                'slug' => 'admission_management',
                'is_limitable' => false
            ],
            [
                'name' => 'Email Notifications',
                'slug' => 'email_notifications',
                'is_limitable' => false
            ],
            [
                'name' => 'Performance Analytics',
                'slug' => 'performance_analytics',
                'is_limitable' => false
            ],
            [
                'name' => 'Bulk Report Card Generation',
                'slug' => 'bulk_report_card',
                'is_limitable' => false
            ],
            [
                'name' => 'Bulk Data Import/Export',
                'slug' => 'bulk_data',
                'is_limitable' => false
            ],
            
            // Premium Features
            [
                'name' => 'CBT Integration (Coming Soon)',
                'slug' => 'cbt_integration',
                'is_limitable' => false
            ],
            [
                'name' => 'Parent & Student Portal (Coming Soon)',
                'slug' => 'portal_access',
                'is_limitable' => false
            ],
            [
                'name' => 'Advanced Reporting',
                'slug' => 'advanced_reporting',
                'is_limitable' => false
            ],
            [
                'name' => 'Priority Support',
                'slug' => 'priority_support',
                'is_limitable' => false
            ],
            [
                'name' => 'Customization Options',
                'slug' => 'customization',
                'is_limitable' => false
            ],
            // Limitable Features
            [
                'name' => 'Students Limit',
                'slug' => 'students_limit',
                'is_limitable' => true
            ],
            [
                'name' => 'Staff Accounts',
                'slug' => 'staff_limit',
                'is_limitable' => true
            ],
            [
                'name' => 'Classes Limit',
                'slug' => 'classes_limit',
                'is_limitable' => true
            ],
        ];

        foreach ($features as $feature) {
            Feature::create($feature);
        }
    }
}
