<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Subject;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class SubjectTableSeeder extends Seeder
{
    protected $islamicSubjects = [
        [
            'name' => 'Al Quran (القرآن)',
            'name_ar' => 'القرآن الكريم',
            'description' => 'Study and memorization of the Holy Quran',
            'description_ar' => 'دراسة وحفظ القرآن الكريم'
        ],
        [
            'name' => 'Hadith (حديث)',
            'name_ar' => 'الحديث النبوي',
            'description' => 'Study of Prophetic traditions',
            'description_ar' => 'دراسة الأحاديث النبوية الشريفة'
        ],
        [
            'name' => 'Fiqh (فقه)',
            'name_ar' => 'الفقه الإسلامي',
            'description' => 'Islamic Jurisprudence',
            'description_ar' => 'دراسة الأحكام الشرعية العملية'
        ],
        [
            'name' => 'Tauheed (توحيد)',
            'name_ar' => 'التوحيد',
            'description' => 'Islamic Monotheism',
            'description_ar' => 'دراسة العقيدة الإسلامية'
        ],
        [
            'name' => 'Nahawu (نحو)',
            'name_ar' => 'النحو',
            'description' => 'Arabic Grammar',
            'description_ar' => 'قواعد اللغة العربية'
        ]
    ];

    protected $regularSubjects = [
        [
            'name' => 'Mathematics',
            'description' => 'Study of numbers, quantities, shapes and patterns'
        ],
        [
            'name' => 'English Language',
            'description' => 'Study of English language and literature'
        ],
        [
            'name' => 'Basic Science',
            'description' => 'Introduction to scientific concepts and methods'
        ],
        [
            'name' => 'Social Studies',
            'description' => 'Study of society and relationships among people'
        ],
        [
            'name' => 'Business Studies',
            'description' => 'Introduction to business concepts and practices'
        ],
        [
            'name' => 'Agricultural Science',
            'description' => 'Study of agriculture and food production'
        ],
        [
            'name' => 'Creative Arts',
            'description' => 'Study of visual and performing arts'
        ],
        [
            'name' => 'Home Economics',
            'description' => 'Study of domestic and household management'
        ],
        [
            'name' => 'Physical Education',
            'description' => 'Study of physical fitness and sports'
        ],
        [
            'name' => 'Computer Studies',
            'description' => 'Study of computers and information technology'
        ],
        [
            'name' => 'Civic Education',
            'description' => 'Study of citizenship and civic responsibilities'
        ],
        [
            'name' => 'Religious Studies',
            'description' => 'Study of religious beliefs and practices'
        ]
    ];

    public function run(): void
    {
        // Get all schools
        $schools = School::all();
        
        // Remove this incomplete line
        // $school = School::where

        foreach ($schools as $school) {
            Log::info('Creating subjects for school: ' . $school->name);
            
            // For schools with Arabic names, add both Islamic and regular subjects
            if (!empty($school->name_ar)) {
                // Add Islamic subjects
                foreach ($this->islamicSubjects as $subject) {
                    Subject::firstOrCreate(
                        [
                            'school_id' => $school->id,
                            'name' => $subject['name'],
                            'slug' => Str::slug($subject['name'])
                        ],
                        [
                            'name_ar' => $subject['name_ar'],
                            'description' => $subject['description'],
                            'description_ar' => $subject['description_ar'],
                            'is_active' => true
                        ]
                    );
                }
            }

            // Add regular subjects for all schools
            foreach ($this->regularSubjects as $subject) {
                Subject::firstOrCreate(
                    [                        'school_id' => $school->id,
                        'name' => $subject['name'],
                        'slug' => Str::slug($subject['name'])
                    ],
                    [
                        'description' => $subject['description'],
                        'is_active' => true
                    ]
                );
            }
        }
        
        Log::info('Subject seeding completed');
    }
}
