<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\ClassRoom;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ClassRoomTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (ClassRoom::count() > 0) {
            return;
        }
        $school = School::find(1);
        // Create a new class room or find an existing one
        $classRoom1 = ClassRoom::firstOrCreate([
            'school_id' => $school->id, // Add this line
            'name' => 'JSS 1',
            'slug' => Str::slug('JSS 1'),
            'capacity' => 30,

        ]);

        // Create a new class room or find an existing one
        $classRoom2 = ClassRoom::firstOrCreate([
            'school_id' => $school->id,
            'name' => 'JSS 2',
            'slug' => Str::slug('JSS 2'),
            'capacity' => 30,

        ]);

        // Create a new class room or find an existing one
        $classRoom3 = ClassRoom::firstOrCreate([
            'school_id' => $school->id,
            'name' => 'JSS 3',
            'slug' => Str::slug('JSS 3'),
            'capacity' => 30,

        ]);

        // Create a new class room or find an existing one
        $classRoom4 = ClassRoom::firstOrCreate([
            'school_id' => $school->id,
            'name' => 'SSS 1',
            'slug' => Str::slug('SSS 1'),
            'capacity' => 30,

        ]);

        // Create a new class room or find an existing one
        $classRoom5 = ClassRoom::firstOrCreate([
            'school_id' => $school->id,
            'name' => 'SSS 2',
            'slug' => Str::slug('SSS 2'),
            'capacity' => 30,

        ]);

        // Create a new class room or find an existing one
        $classRoom6 = ClassRoom::firstOrCreate([
            'school_id' => $school->id,
            'name' => 'SSS 3',
            'slug' => Str::slug('SSS 3'),
            'capacity' => 30,

        ]);


        $school2 = School::find(2);

        // Create a new class room or find an existing one
        $classRoom7 = ClassRoom::firstOrCreate([
            'school_id' => $school2->id,
            'name' => 'Primary 1',
            'slug' => Str::slug('Primary 1'),
            'capacity' => 25,
        ]);

        $classRoom8 = ClassRoom::firstOrCreate([
            'school_id' => $school2->id,
            'name' => 'Primary 2',
            'slug' => Str::slug('Primary 2'),
            'capacity' => 25,
        ]);

        $classRoom9 = ClassRoom::firstOrCreate([
            'school_id' => $school2->id,
            'name' => 'Primary 3',
            'slug' => Str::slug('Primary 3'),
            'capacity' => 25,
        ]);

        $classRoom10 = ClassRoom::firstOrCreate([
            'school_id' => $school2->id,
            'name' => 'Primary 4',
            'slug' => Str::slug('Primary 4'),
            'capacity' => 25,
        ]);

        $classRoom11 = ClassRoom::firstOrCreate([
            'school_id' => $school2->id,
            'name' => 'Primary 5',
            'slug' => Str::slug('Primary 5'),
            'capacity' => 25,
        ]);

        $classRoom12 = ClassRoom::firstOrCreate([
            'school_id' => $school2->id,
            'name' => 'Primary 6',
            'slug' => Str::slug('Primary 6'),
            'capacity' => 25,
        ]);
        
    }
}
