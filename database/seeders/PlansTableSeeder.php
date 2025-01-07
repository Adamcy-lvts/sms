<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Helpers\PaystackHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlansTableSeeder extends Seeder
{
    /**
     * Base plans configuration
     * This defines our plan tiers with all their features and pricing
     */
    protected $plans = [
        [
            'name' => "Basic",
            'description' => "Essential features for small to medium schools",
            'monthly_price' => 15000,
            'yearly_discount' => 20, // 20% off for yearly
            'max_students' => 500,
            'max_staff' => 5,
            'max_classes' => 15,
            'trial_period' => 30,
            'badge_color' => '#10B981', // Emerald-500
            'features' => [
                'Profile Management',
                'Role-Based Access Control',
                'Financial Management',
                'Attendance Tracking',
                'Report Card Generation',
                'Basic Analytics',
                '500 Students Limit',
                '5 Staff Accounts',
                'Email Support'
            ],
            'cto' => 'Start your subscription'
        ],
        [
            'name' => "Standard",
            'description' => "Advanced features for growing schools",
            'monthly_price' => 25000,
            'yearly_discount' => 25, // 25% off for yearly
            'max_students' => 1000,
            'max_staff' => 15,
            'max_classes' => 30,
            'trial_period' => 30,
            'badge_color' => '#6366F1', // Indigo-500
            'features' => [
                'All Basic Features',
                'Admission Management',
                'SMS Notifications',
                'Performance Analytics',
                'Library Management',
                'Bulk Data Import/Export',
                '1000 Students Limit',
                '15 Staff Accounts',
                'Priority Email Support',
                'Staff Management'
            ],
            'cto' => 'Start your subscription'
        ],
        [
            'name' => "Premium",
            'description' => "Complete solution for large institutions",
            'monthly_price' => 50000,
            'yearly_discount' => 30, // 30% off for yearly
            'max_students' => null, // Unlimited
            'max_staff' => null,  // Unlimited
            'max_classes' => null, // Unlimited
            'trial_period' => 30,
            'badge_color' => '#EC4899', // Pink-500
            'features' => [
                'All Standard Features',
                'CBT Integration',
                'Parent & Student Portal',
                'Advanced Reporting',
                'API Access',
                'Priority Support',
                'Unlimited Students',
                'Unlimited Staff',
                'Dedicated Account Manager',
                'Custom Integration Support',
                '24/7 Phone Support'
            ],
            'cto' => 'Start your subscription'
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Disable foreign key checks to allow truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Plan::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        // Skip if plans already exist
        if (Plan::count() > 0) {
            Log::info('Plans already exist, skipping seeder.');
            return;
        }

        DB::beginTransaction();

        try {
            foreach ($this->plans as $planData) {
                // Create monthly plan first
                $monthlyPlan = $this->createPlanVariant($planData, 'monthly');
                if (!$monthlyPlan) {
                    throw new \Exception("Failed to create monthly plan for {$planData['name']}");
                }

                // Create yearly plan
                $yearlyPlan = $this->createPlanVariant($planData, 'annually');
                if (!$yearlyPlan) {
                    throw new \Exception("Failed to create yearly plan for {$planData['name']}");
                }
            }

            DB::commit();
            Log::info('Plans seeded successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to seed plans: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a plan variant (monthly or yearly)
     */
    protected function createPlanVariant(array $planData, string $interval): ?Plan
    {
        try {
            $isYearly = $interval === 'annually';

            // Calculate prices
            if ($isYearly) {
                // For yearly plans
                $basePrice = $planData['monthly_price'] * 12;
                $discount = ($basePrice * ($planData['yearly_discount'] / 100));
                $discountedPrice = $basePrice - $discount;
            } else {
                // For monthly plans
                $basePrice = $planData['monthly_price'];
                $discountedPrice = null;
            }

            // Create Paystack plan with appropriate price
            $paystackPlan = PaystackHelper::createPlan([
                'name' => $planData['name'],
                'description' => $planData['description'],
                'amount' => ($discountedPrice ?? $basePrice) * 100, // Convert to kobo
                'interval' => $interval,
                'currency' => 'NGN',
            ]);

            // Create local plan
            $plan = Plan::create([
                'name' => $planData['name'],
                'description' => $planData['description'],
                'price' => $basePrice,
                'discounted_price' => $discountedPrice,
                'interval' => $interval,
                'duration' => $isYearly ? 365 : 30,
                'features' => $planData['features'],
                'plan_code' => $paystackPlan['data']['plan_code'],
                'yearly_discount' => $isYearly ? $planData['yearly_discount'] : 0,
                'max_students' => $planData['max_students'],
                'max_staff' => $planData['max_staff'],
                'max_classes' => $planData['max_classes'],
                'trial_period' => $planData['trial_period'],
                'has_trial' => $planData['trial_period'] > 0,
                'badge_color' => $planData['badge_color'],
                'cto' => $planData['cto'],
                'status' => 'active'
            ]);

            return $plan;
        } catch (\Exception $e) {
            Log::error("Failed to create plan variant", [
                'name' => $planData['name'] ?? $planData['name'],
                'interval' => $interval,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
}
