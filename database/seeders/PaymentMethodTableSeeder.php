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

        $paymentMethods = [
            [
               
                'name' => 'Bank Transfer',
                'slug' => 'bank-transfer',
                'description' => 'Payment made through bank transfer',
                'logo' => 'bank-transfer.png',
                'active' => true
            ],
            [
               
                'name' => 'POS',
                'slug' => 'pos',
                'description' => 'Payment made through POS',
                'logo' => 'pos.png',
                'active' => true
            ],
            [
               
                'name' => 'Cash',
                'slug' => 'cash',
                'description' => 'Payment made with cash',
                'logo' => 'cash.png',
                'active' => true
            ],
            [
               
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
        // All schools
        $schools = \App\Models\School::all();
        foreach ($schools as $school) {
            foreach ($paymentMethods as $paymentMethod) {
                $paymentMethod['school_id'] = $school->id;
                \App\Models\PaymentMethod::create($paymentMethod);
            }
        }
    }
}
