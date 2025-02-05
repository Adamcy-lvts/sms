<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\PaymentType;
use Illuminate\Database\Seeder;

class PaymentTypeSeeder extends Seeder
{
    protected $paymentTypes = [
        // Tuition fees by level
        [
            'name' => 'Tuition Fee (Nursery)',
            'category' => 'service_fee',
            'amount' => 75000,
            'is_tuition' => true,
            'class_level' => 'nursery',
            'installment_allowed' => true,
            'min_installment_amount' => 25000,
            'has_due_date' => true,
            'description' => 'Nursery school tuition fees'
        ],
        [
            'name' => 'Tuition Fee (Primary)',
            'category' => 'service_fee',
            'amount' => 90000,
            'is_tuition' => true,
            'class_level' => 'primary',
            'installment_allowed' => true,
            'min_installment_amount' => 30000,
            'has_due_date' => true,
            'description' => 'Primary school tuition fees'
        ],
        [
            'name' => 'Tuition Fee (Secondary)',
            'category' => 'service_fee',
            'amount' => 120000,
            'is_tuition' => true,
            'class_level' => 'secondary',
            'installment_allowed' => true,
            'min_installment_amount' => 40000,
            'has_due_date' => true,
            'description' => 'Secondary school tuition fees'
        ],
        // Physical items
        [
            'name' => 'School Uniform',
            'category' => 'physical_item',
            'amount' => 15000,
            'description' => 'Complete school uniform set'
        ],
        [
            'name' => 'Books',
            'category' => 'physical_item',
            'amount' => 25000,
            'description' => 'Required textbooks and workbooks'
        ],
        // Other fees
        [
            'name' => 'Development Levy',
            'category' => 'service_fee',
            'amount' => 10000,
            'description' => 'School development fee'
        ],
        // ... Add other payment types as needed
    ];

    public function run(): void
    {
        $school = School::where('slug', 'khalil-integrated-academy')->firstOrFail();

        foreach ($this->paymentTypes as $type) {
            PaymentType::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'name' => $type['name']
                ],
                [
                    'category' => $type['category'],
                    'amount' => $type['amount'],
                    'is_tuition' => $type['is_tuition'] ?? false,
                    'class_level' => $type['class_level'] ?? null,
                    'installment_allowed' => $type['installment_allowed'] ?? false,
                    'min_installment_amount' => $type['min_installment_amount'] ?? null,
                    'has_due_date' => $type['has_due_date'] ?? false,
                    'description' => $type['description'],
                    'active' => true
                ]
            );
        }
    }
}
