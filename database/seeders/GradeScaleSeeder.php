<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\GradingScale;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class GradeScaleSeeder extends Seeder 
{
    public function run(): void 
    {
        $gradeScales = [
            [
                'grade' => 'A',
                'min_score' => 70,
                'max_score' => 100,
                'remark' => 'Excellent',
                'is_active' => true
            ],
            [
                'grade' => 'B',
                'min_score' => 60,
                'max_score' => 69,
                'remark' => 'Very Good',
                'is_active' => true
            ],
            [
                'grade' => 'C',
                'min_score' => 50,
                'max_score' => 59,
                'remark' => 'Good',
                'is_active' => true
            ],
            [
                'grade' => 'D',
                'min_score' => 40,
                'max_score' => 49,
                'remark' => 'Fair',
                'is_active' => true
            ],
            [
                'grade' => 'F',
                'min_score' => 0,
                'max_score' => 39,
                'remark' => 'Failed',
                'is_active' => true
            ]
        ];

        // Get all schools
        $schools = School::all();

        foreach ($schools as $school) {
            foreach ($gradeScales as $scale) {
                GradingScale::firstOrCreate(
                    [
                        'school_id' => $school->id,
                        'grade' => $scale['grade'],
                        'min_score' => $scale['min_score']
                    ],
                    $scale
                );
            }
        }
    }
}