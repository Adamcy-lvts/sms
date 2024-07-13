<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DesignationTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $school = \App\Models\School::first();
        // Insert some sample data
        $designations = [
            ['name' => 'Principal', 'description' => 'The head of the school', 'school_id' => $school->id],
            ['name' => 'Vice Principal', 'description' => 'The deputy head of the school','school_id' => $school->id],
            ['name' => 'Head Teacher', 'description' => 'The head of the primary section', 'school_id' => $school->id],
            ['name' => 'Deputy Head Teacher', 'description' => 'The deputy head of the primary section', 'school_id' => $school->id],
            ['name' => 'Head of Department', 'description' => 'The head of a department', 'school_id' => $school->id],
            ['name' => 'Teacher', 'description' => 'A classroom teacher', 'school_id' => $school->id],
            ['name' => 'Clerk', 'description' => 'A school clerk', 'school_id' => $school->id],
            ['name' => 'Librarian', 'description' => 'A school librarian', 'school_id' => $school->id],
            ['name' => 'Security Guard', 'description' => 'A school security guard', 'school_id' => $school->id],
            ['name' => 'Cleaner', 'description' => 'A school cleaner', 'school_id' => $school->id],
            ['name' => 'Driver', 'description' => 'A school driver', 'school_id' => $school->id],
            ['name' => 'Cook', 'description' => 'A school cook', 'school_id' => $school->id],
            ['name' => 'Gardener', 'description' => 'A school gardener', 'school_id' => $school->id],
            ['name' => 'Accountant', 'description' => 'A school accountant', 'school_id' => $school->id],
            ['name' => 'Bursar', 'description' => 'A school bursar', 'school_id' => $school->id],
            ['name' => 'Secretary', 'description' => 'A school secretary', 'school_id' => $school->id],
          
        ];

        if (\App\Models\Designation::count() > 0) {
            return;
        }
        foreach ($designations as $designation) {
            \App\Models\Designation::create($designation);
        }
    }
}
