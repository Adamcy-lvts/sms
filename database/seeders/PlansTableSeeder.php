<?php

namespace Database\Seeders;

use App\Helpers\PaystackHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Unicodeveloper\Paystack\Facades\Paystack;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PlansTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        // Define plans with features

        $plans = [
            [
                'name' => "Basic",
                'description' => "Access to essential school management features including student enrollment, admissions processing, payments management, classroom management, and basic school analytics.",
                'amount' => 10000 * 100, // Convert to kobo
                'interval' => "monthly",
                'currency' => "NGN",
                'features' => [
                    'Student Management',
                    'Admission Management',
                    'Session & Term Management',
                    'Classroom Management',
                    'Exam Management',
                    'Student Profile',
                    'Subjects Management',
                    'Role-Based Access Control',
                    'Teacher Profiles Management',
                    'Staff Management',
                    'Accounting Management',
                    'Basic Analytics',
                    'Send Email Noifications',
                    '500 Students Limit',
                    'Up to 5 Staff Accounts',

                ]
            ],
            [
                'name' => "Standard",
                'description' => "Includes all Basic features plus advanced analytics and reporting, staff and teacher management, role-based access control, library management, and student portal access.",
                'amount' => 25000 * 100,
                'interval' => "monthly",
                'currency' => "NGN",
                'features' => [
                    'All Basic Features',
                    'Advanced Analytics & Reporting',
                    'Import and Export Data from Excel',
                    'Receipt & Invoice Generation',
                    'Attendance Tracking',
                    'Books Management',
                    'Student Performance Metrics',
                    'Up to 15 Staff Accounts',
                    'Up to 1000 Students Limit',

                ]
            ],
            [
                'name' => "Premium",
                'description' => "Includes all Standard features plus computer-based test integration, real-time data analytics, custom feature requests, enhanced security protocols, and student and parent portal access.",
                'amount' => 50000 * 100,
                'interval' => "monthly",
                'currency' => "NGN",
                'features' => [
                    'All Standard Features',
                    'Computer-Based Test Integration',
                    'Real-Time Advanced Data Analytics',
                    'Custom Feature Requests',
                    'Enhanced Security Protocols',
                    'Student & Parent Dashboard Access',
                    'Unlimited Staff Accounts',
                    'Priority Support',
                    'Unlimited Students Limit',
                ]
            ],
        ];
        // Log::info('Attempting to create Paystack plan:', $plans);
        // Create each plan on Paystack and in the local database
        // Check if plans already exist in the database
        if (DB::table('plans')->count() === 0) {
            // Create each plan on Paystack and in the local database
            foreach ($plans as $plan) {
                // Log::info('Attempting to create Paystack plan:', $plan);
                $response = PaystackHelper::createPlan([
                    'name' => $plan["name"],
                    'description' => $plan["description"],
                    'amount' => $plan["amount"],
                    'interval' => $plan["interval"],
                    'currency' => $plan["currency"],
                ]);
                // Log::info('Response from Paystack:', $response);
                if ($response['status']) {
                    // If Paystack plan creation was successful, save to local DB
                    DB::table('plans')->insert([
                        'name' => $plan['name'],
                        'price' => $response['data']['amount'] / 100,
                        'description' => $plan['description'],
                        'duration' => 30, // Assume 30 days for monthly plans
                        'features' => json_encode($plan['features']), // Convert features array to JSON string
                        'plan_code' => $response['data']['plan_code'], // Store Paystack plan code
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }
    }
}
