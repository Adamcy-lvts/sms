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
            ['name' => 'active', 'type' => 'general'],
            ['name' => 'inactive', 'type' => 'general'],
            ['name' => 'blocked', 'type' => 'general'],
            ['name' => 'pending', 'type' => 'payment'],
            ['name' => 'completed', 'type' => 'payment'],
            ['name' => 'failed', 'type' => 'payment'],
            ['name' => 'refunded', 'type' => 'payment'],
            ['name' => 'paid', 'type' => 'payment'],
            ['name' => 'unpaid', 'type' => 'payment'],
            ['name' => 'approved', 'type' => 'admission'],
            ['name' => 'rejected', 'type' => 'admission'],
            ['name' => 'processing', 'type' => 'admission'],
            ['name' => 'active', 'type' => 'student'],
            ['name' => 'graduated', 'type' => 'student'],
            ['name' => 'suspended', 'type' => 'student'],
            ['name' => 'expelled', 'type' => 'student'],
            ['name' => 'promoted', 'type' => 'student'],
            ['name' => 'demoted', 'type' => 'student'],
            ['name' => 'transferred', 'type' => 'student'],
            ['name' => 'withdrawn', 'type' => 'student'],
            ['name' => 'archived', 'type' => 'student'],
            ['name' => 'deceased', 'type' => 'student'],
            ['name' => 'active', 'type' => 'staff'],
            ['name' => 'resigned', 'type' => 'staff'],
            ['name' => 'terminated', 'type' => 'staff'],
            ['name' => 'suspended', 'type' => 'staff'],
            ['name' => 'archived', 'type' => 'staff'],
            ['name' => 'deceased', 'type' => 'staff'],

        ];

        if (count($statuses) === 0) {
            return;
        }
        foreach ($statuses as $status) {
            \App\Models\Status::create($status);
        }
    }
}
