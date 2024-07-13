<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $school = \App\Models\School::find(1);

        $paymentMethods = [
            [
                'school_id' => $school->id,
                'name' => 'Bank Transfer',
                'slug' => 'bank-transfer',
                'description' => 'Payment made through bank transfer',
                'logo' => 'bank-transfer.png',
                'active' => true
            ],
            [
                'school_id' => $school->id,
                'name' => 'POS',
                'slug' => 'pos',
                'description' => 'Payment made through POS',
                'logo' => 'pos.png',
                'active' => true
            ],
            [
                'school_id' => $school->id,
                'name' => 'Cash',
                'slug' => 'cash',
                'description' => 'Payment made with cash',
                'logo' => 'cash.png',
                'active' => true
            ],
            [
                'school_id' => $school->id,
                'name' => 'Cheque',
                'slug' => 'cheque',
                'description' => 'Payment made with cheque',
                'logo' => 'cheque.png',
                'active' => true
            ],
        ];

        if (\App\Models\PaymentMethod::count() > 0) {
            return;
        }
        foreach ($paymentMethods as $paymentMethod) {
            \App\Models\PaymentMethod::create($paymentMethod);
        }
    }
}
