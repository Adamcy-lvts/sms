<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Subject;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SubjectTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $school = School::find(1);
        // Create a new subject or find an existing one
        $subject1 = Subject::firstOrCreate([
            'school_id' => $school->id, // Add this line
            'name' => 'Mathematics',
            'slug' => Str::slug('Mathematics'),
            'description' => 'Mathematics is the study of numbers, shapes and patterns.',
        ]);

        // Create a new subject or find an existing one
        $subject2 = Subject::firstOrCreate([
            'school_id' => $school->id, // Add this line
            'name' => 'English Language',
            'slug' => Str::slug('English Language'),
            'description' => 'English Language is the study of the English language.',
        ]);

        // Create a new subject or find an existing one
        $subject3 = Subject::firstOrCreate([
            'school_id' => $school->id, // Add this line
            'name' => 'Physics',
            'slug' => Str::slug('Physics'),
            'description' => 'Physics is the study of matter and energy.',
        ]);

        // Create a new subject or find an existing one
        $subject4 = Subject::firstOrCreate([
            'school_id' => $school->id, // Add this line
            'name' => 'Chemistry',
            'slug' => Str::slug('Chemistry'),
            'description' => 'Chemistry is the study of matter and its properties.',
        ]);

        // Create a new subject or find an existing one
        $subject5 = Subject::firstOrCreate([
            'school_id' => $school->id, // Add this line
            'name' => 'Biology',
            'slug' => Str::slug('Biology'),
            'description' => 'Biology is the study of living organisms.',
        ]);

        $school2 = School::find(2);
        // Create a new subject or find an existing one
        $subject6 = Subject::firstOrCreate([
            'school_id' => $school2->id, // Add this line
            'name' => 'History',
            'slug' => Str::slug('History'),
            'description' => 'History is the study of past events.',
        ]);
        $subject7 = Subject::firstOrCreate([
            'school_id' => $school2->id, // Add this line
            'name' => 'Geography',
            'slug' => Str::slug('Geography'),
            'description' => 'Geography is the study of the Earth and its features.',
        ]);
        $subject8 = Subject::firstOrCreate([
            'school_id' => $school2->id, // Add this line
            'name' => 'Computer Science',
            'slug' => Str::slug('Computer Science'),
            'description' => 'Computer Science is the study of computers and computational systems.',
        ]);
        $subject9 = Subject::firstOrCreate([
            'school_id' => $school2->id, // Add this line
            'name' => 'Economics',
            'slug' => Str::slug('Economics'),
            'description' => 'Economics is the study of the production, distribution, and consumption of goods and services.',
        ]);
        $subject10 = Subject::firstOrCreate([
            'school_id' => $school2->id, // Add this line
            'name' => 'Government',
            'slug' => Str::slug('Government'),
            'description' => 'Government is the study of the political systems and structures of a country.',
        ]);
    }
}
