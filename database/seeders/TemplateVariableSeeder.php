<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;
use App\Models\TemplateVariable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TemplateVariableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $systemVariables = [
            // Student variables
            [
                'name' => 'student_name',
                'display_name' => 'Student Name',
                'category' => 'all',
                'field_type' => 'text',
                'mapping' => 'student.full_name',
                'is_system' => true
            ],
            [
                'name' => 'admission_number',
                'display_name' => 'Admission Number',
                'category' => 'admission',
                'field_type' => 'text',
                'mapping' => 'student.admission_number',
                'is_system' => true
            ],
            // Add more system variables...
        ];

        // Get all schools
        $schools = School::all();

        foreach ($schools as $school) {
            foreach ($systemVariables as $variable) {
                TemplateVariable::create([
                    'school_id' => $school->id,
                    ...$variable
                ]);
            }
        }
    }
}
