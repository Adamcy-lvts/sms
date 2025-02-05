<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Feature;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanFeatureSeeder extends Seeder
{
    /**
     * Features for each plan tier
     */
    protected array $planFeatures = [
        'Basic' => [
            'profile_management',
            'rbac',
            'financial_management',
            'attendance_tracking',
            'report_card_generation',
            'basic_analytics'
        ],
        'Standard' => [
            'admission_management',
            'email_notifications',
            'performance_analytics',
            'bulk_report_card',
            'bulk_data'
        ],
        'Premium' => [
            'cbt_integration',
            'portal_access',
            'advanced_reporting',
            'priority_support',
            'customization'
        ]
    ];

    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('feature_plan')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $plans = Plan::all();

        foreach ($plans as $plan) {
            // Get features based on plan name
            $features = $this->getFeaturesForPlan($plan->name);
            
            // Attach all applicable features
            foreach ($features as $slug) {
                $feature = Feature::where('slug', $slug)->first();
                if ($feature) {
                    $plan->features()->attach($feature->id);
                }
            }
        }
    }

    /**
     * Get cumulative features for a plan
     */
    private function getFeaturesForPlan(string $planName): array
    {
        $features = [];

        // Add basic features for all plans
        $features = array_merge($features, $this->planFeatures['Basic']);

        // Add standard features for Standard and Premium plans
        if (in_array($planName, ['Standard', 'Premium'])) {
            $features = array_merge($features, $this->planFeatures['Standard']);
        }

        // Add premium features for Premium plan
        if ($planName === 'Premium') {
            $features = array_merge($features, $this->planFeatures['Premium']);
        }

        return array_unique($features);
    }
}
