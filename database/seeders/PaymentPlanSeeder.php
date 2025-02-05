<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\PaymentType;
use App\Models\PaymentPlan;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Seeder;

class PaymentPlanSeeder extends Seeder
{
    public function run(): void
    {
        try {
            $school = School::where('slug', 'khalil-integrated-academy')->firstOrFail();
            
            // Get payment types for tuition
            $tuitionTypes = PaymentType::where('school_id', $school->id)
                ->where('is_tuition', true)
                ->get()
                ->keyBy('class_level');

            if ($tuitionTypes->isEmpty()) {
                Log::warning('No tuition payment types found for school', [
                    'school_id' => $school->id
                ]);
                return;
            }

            $plans = $this->getPaymentPlans($tuitionTypes);

            foreach ($plans as $plan) {
                if (!isset($tuitionTypes[$plan['class_level']])) {
                    Log::warning('Missing tuition type for class level', [
                        'class_level' => $plan['class_level'],
                        'school_id' => $school->id
                    ]);
                    continue;
                }

                PaymentPlan::firstOrCreate(
                    [
                        'school_id' => $school->id,
                        'payment_type_id' => $tuitionTypes[$plan['class_level']]->id,
                        'class_level' => $plan['class_level']
                    ],
                    [
                        'name' => $plan['name'],
                        'term_amount' => $plan['term_amount'],
                        'session_amount' => $plan['session_amount']
                    ]
                );
            }

            Log::info('Payment plans created successfully', [
                'school_id' => $school->id,
                'plans_count' => count($plans)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create payment plans', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function getPaymentPlans(): array
    {
        return [
            [
                'name' => 'Nursery School Fees',
                'class_level' => 'nursery', 
                'term_amount' => 25000.00,
                'session_amount' => 75000.00,
            ],
            [
                'name' => 'Primary School Fees',
                'class_level' => 'primary',
                'term_amount' => 30000.00,
                'session_amount' => 90000.00,
            ],
            [
                'name' => 'Secondary School Fees',
                'class_level' => 'secondary',
                'term_amount' => 40000.00,
                'session_amount' => 120000.00,
            ]
        ];
    }
}
