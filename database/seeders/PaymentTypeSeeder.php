<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\PaymentType;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class PaymentTypeSeeder extends Seeder
{
    protected $paymentTypes = [
        [
            'name' => 'School Fee',
            'amount' => 75000,
            'description' => 'Basic school fee per term'
        ],
        [
            'name' => 'Development Levy',
            'amount' => 10000,
            'description' => 'School development and maintenance fee'
        ],
        [
            'name' => 'Uniform',
            'amount' => 15000,
            'description' => 'Complete school uniform set'
        ],
        [
            'name' => 'Books',
            'amount' => 25000,
            'description' => 'Required textbooks and workbooks'
        ],
        [
            'name' => 'Laboratory Fee',
            'amount' => 5000,
            'description' => 'Science laboratory usage and materials'
        ],
        [
            'name' => 'Sports Wear',
            'amount' => 8000,
            'description' => 'Physical education and sports uniform'
        ],
        [
            'name' => 'ID Card',
            'amount' => 2000,
            'description' => 'Student identification card'
        ],
        [
            'name' => 'Library Fee',
            'amount' => 5000,
            'description' => 'Library services and resources'
        ],
        [
            'name' => 'Computer Lab Fee',
            'amount' => 7500,
            'description' => 'Computer laboratory usage and maintenance'
        ],
        [
            'name' => 'Medical Fee',
            'amount' => 3000,
            'description' => 'Basic medical services and first aid'
        ],
        [
            'name' => 'Extra-Curricular Activities',
            'amount' => 4000,
            'description' => 'Clubs and after-school activities'
        ],
        [
            'name' => 'Examination Fee',
            'amount' => 5000,
            'description' => 'Term examination materials and logistics'
        ],
        [
            'name' => 'School Bus Service',
            'amount' => 20000,
            'description' => 'Optional school transportation service'
        ],
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
                    'amount' => $type['amount'],
                    'description' => $type['description'],
                    'active' => true
                ]
            );
        }
    }
}
