<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StatusTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'active'],
            ['name' => 'inactive'],
            ['name' => 'pending'],
            ['name' => 'suspended'],
            ['name' => 'blocked'],
            ['name' => 'deleted'],
            ['name' => 'expired'],
            ['name' => 'completed'],
            ['name' => 'processing'],
            ['name' => 'failed'],
            ['name' => 'cancelled'],
            ['name' => 'approved'],
            ['name' => 'rejected'],
            ['name' => 'verified'],
            ['name' => 'unverified'],
            ['name' => 'paid'],
            ['name' => 'unpaid'],
            ['name' => 'refunded'],
            
        ];

        if (count($statuses) === 0) {
            return;
        }
        foreach ($statuses as $status) {
            \App\Models\Status::create($status);
        }
    }
}
