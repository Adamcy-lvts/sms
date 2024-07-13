<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Term;
use App\Models\School;
use App\Models\AcademicSession;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SessionAndTermSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (AcademicSession::count() > 0) {
            return;
        }
        $school = School::find(1);

        // Example Academic Sessions

        $sessions = [
            [
                'school_id' => $school->id,
                'name' => '2023/2024',
                'start_date' => Carbon::createFromDate(2023, 9, 4),
                'end_date' => Carbon::createFromDate(2024, 6, 31),
            ],
            [
                'school_id' => $school->id,
                'name' => '2024/2025',
                'start_date' => Carbon::createFromDate(2024, 9, 4),
                'end_date' => Carbon::createFromDate(2025, 6, 31),
            ],
            [
                'school_id' => $school->id,
                'name' => '2025/2026',
                'start_date' => Carbon::createFromDate(2025, 9, 4),
                'end_date' => Carbon::createFromDate(2026, 6, 31),
            ],
        ];

        foreach ($sessions as $sessionData) {
            $session = AcademicSession::create($sessionData);

            // Example Terms for each Academic Session
            $terms = [
                ['name' => 'First Term', 'start_date' => $session->start_date, 'end_date' => $session->start_date->copy()->addMonths(3), 'school_id' => $school->id],
                ['name' => 'Second Term', 'start_date' => $session->start_date->copy()->addMonths(4), 'end_date' => $session->start_date->copy()->addMonths(7), 'school_id' => $school->id],
                ['name' => 'Third Term', 'start_date' => $session->start_date->copy()->addMonths(8), 'end_date' => $session->end_date, 'school_id' => $school->id],
            ];

            foreach ($terms as $termData) {
                $termData['academic_session_id'] = $session->id;
                Term::create($termData);
            }
        }
    }
}
