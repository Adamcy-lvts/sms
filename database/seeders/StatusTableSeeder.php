<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusTableSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            // Student Statuses - Core statuses that appear in status column
            ['name' => 'active', 'type' => 'student'],
            ['name' => 'inactive', 'type' => 'student'],
            ['name' => 'suspended', 'type' => 'student'],
            ['name' => 'graduated', 'type' => 'student'],
            ['name' => 'transferred', 'type' => 'student'],
            ['name' => 'withdrawn', 'type' => 'student'],
            ['name' => 'expelled', 'type' => 'student'],
            ['name' => 'deceased', 'type' => 'student'],
            
            // Payment Statuses
            ['name' => 'pending', 'type' => 'payment'],
            ['name' => 'partial', 'type' => 'payment'],
            ['name' => 'paid', 'type' => 'payment'],
            ['name' => 'overdue', 'type' => 'payment'],
            ['name' => 'refunded', 'type' => 'payment'],
            
            ['name' => 'active', 'type' => 'user'],
            ['name' => 'inactive', 'type' => 'user'],
            ['name' => 'blocked', 'type' => 'user'],
     

            // Staff Statuses
            ['name' => 'active', 'type' => 'staff'],
            ['name' => 'inactive', 'type' => 'staff'],
            ['name' => 'resigned', 'type' => 'staff'],
            ['name' => 'terminated', 'type' => 'staff'],
            ['name' => 'retired', 'type' => 'staff'],
            
            // Admission Statuses
            ['name' => 'pending', 'type' => 'admission'],
            ['name' => 'approved', 'type' => 'admission'],
            ['name' => 'rejected', 'type' => 'admission'],
        ];

        foreach ($statuses as $status) {
            Status::firstOrCreate(['name' => $status['name'], 'type' => $status['type']], $status);
        }
    }
}
