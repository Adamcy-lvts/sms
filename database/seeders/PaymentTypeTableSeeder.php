<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $school = \App\Models\School::find(1);

        $paymentTypes = [
            [
                'school_id' => $school->id,
                'name' => 'School Fees',
                'amount' => 40000,
                'description' => 'Payment made for school fees',
                'active' => true
            ],
            [
                'school_id' => $school->id,
                'name' => 'Exam Fees',
                'amount' => 10000,
                'description' => 'Payment made for exam fees',
                'active' => true
            ],
            [
                'school_id' => $school->id,
                'name' => 'Library Fees',
                'amount' => 5000, // Add this line
                'description' => 'Payment made for library fees',
                'active' => true
            ],
            [
                'school_id' => $school->id,
                'name' => 'Laboratory Fees',
                'amount' => 10000, // Add this line
                'description' => 'Payment made for laboratory fees',
                'active' => true
            ],
        ];

        if (\App\Models\PaymentType::count() > 0) {
            return;
        }
        foreach ($paymentTypes as $paymentType) {
            \App\Models\PaymentType::create($paymentType);
        }
    }
}
