<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Term;
use App\Models\School;
use App\Models\AcademicSession;
use Illuminate\Database\Seeder;
use App\Models\SchoolCalendarEvent;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SessionAndTermSeeder extends Seeder
{
    public function run(): void
    {
        if (AcademicSession::count() > 0) {
            return;
        }

        // Get all schools
        $schools = School::all();

        foreach ($schools as $school) {
            $this->seedSchoolData($school);
        }
    }

    protected function seedSchoolData(School $school): void
    {
        $sessions = $this->getSessionData();

        foreach ($sessions as $sessionData) {
            // Create academic session
            $session = AcademicSession::create([
                'school_id' => $school->id,
                'name' => $sessionData['name'],
                'start_date' => Carbon::parse($sessionData['start_date']),
                'end_date' => Carbon::parse($sessionData['end_date']),
                'is_current' => $sessionData['is_current']
            ]);

            $this->createTermsAndEvents($school, $session, $sessionData);
        }
    }

    protected function createTermsAndEvents(School $school, AcademicSession $session, array $sessionData): void
    {
        // Create terms
        foreach ($sessionData['terms'] as $termData) {
            $term = Term::create([
                'school_id' => $school->id,
                'academic_session_id' => $session->id,
                'name' => $termData['name'],
                'start_date' => Carbon::parse($termData['start_date']),
                'end_date' => Carbon::parse($termData['end_date']),
                'is_current' => ($sessionData['is_current'] && Carbon::now()->between(
                    Carbon::parse($termData['start_date']),
                    Carbon::parse($termData['end_date'])
                ))
            ]);

            // Create vacation period
            SchoolCalendarEvent::create([
                'school_id' => $school->id,
                'title' => "{$termData['name']} Vacation",
                'description' => "Vacation period after {$termData['name']}",
                'start_date' => Carbon::parse($termData['vacation_start']),
                'end_date' => Carbon::parse($termData['vacation_end']),
                'type' => 'break',
                'excludes_attendance' => true
            ]);

            // Create midterm break
            SchoolCalendarEvent::create([
                'school_id' => $school->id,
                'title' => "{$termData['name']} Mid-term Break",
                'description' => "Mid-term break for {$termData['name']}",
                'start_date' => Carbon::parse($termData['midterm_break'][0]),
                'end_date' => Carbon::parse($termData['midterm_break'][1]),
                'type' => 'break',
                'excludes_attendance' => true
            ]);
        }

        // Create holidays
        foreach ($sessionData['holidays'] as $holiday) {
            $duration = $holiday['duration'] ?? 1;
            $startDate = Carbon::parse($holiday['date']);

            SchoolCalendarEvent::create([
                'school_id' => $school->id,
                'title' => $holiday['title'],
                'description' => "{$holiday['title']} Holiday",
                'start_date' => $startDate,
                'end_date' => $startDate->copy()->addDays($duration - 1),
                'type' => 'holiday',
                'excludes_attendance' => true
            ]);
        }
    }

    protected function getSessionData(): array
    {
        return $this->getExistingSessionData();
    }

    protected function getExistingSessionData(): array
    {
        return [
            // 2022/2023 Session
            [
                'name' => '2022/2023',
                'start_date' => '2022-09-12',
                'end_date' => '2023-08-01',
                'is_current' => false,
                'terms' => [
                    [
                        'name' => 'First Term',
                        'start_date' => '2022-09-12',
                        'end_date' => '2022-12-16',
                        'vacation_start' => '2022-12-17',
                        'vacation_end' => '2023-01-08',
                        'midterm_break' => ['2022-10-24', '2022-10-28']
                    ],
                    [
                        'name' => 'Second Term',
                        'start_date' => '2023-01-09',
                        'end_date' => '2023-03-31',
                        'vacation_start' => '2023-04-01',
                        'vacation_end' => '2023-04-30',
                        'midterm_break' => ['2023-02-13', '2023-02-17']
                    ],
                    [
                        'name' => 'Third Term',
                        'start_date' => '2023-05-01',
                        'end_date' => '2023-08-01',
                        'vacation_start' => '2023-08-02',
                        'vacation_end' => '2023-09-10',
                        'midterm_break' => ['2023-06-12', '2023-06-16']
                    ]
                ],
                'holidays' => [
                    ['title' => 'Eid-El Maulud', 'date' => '2022-10-08'],
                    ['title' => 'Christmas', 'date' => '2022-12-25', 'duration' => 2],
                    ['title' => 'Eid-Fitr', 'date' => '2023-04-21', 'duration' => 3],
                    ['title' => 'Easter', 'date' => '2023-04-07', 'duration' => 4], // Good Friday through Easter Monday
                    ['title' => 'Children Day', 'date' => '2023-05-27'],
                    ['title' => 'Eid-El Kabir', 'date' => '2023-06-28', 'duration' => 2],
                    ['title' => 'Democracy Day', 'date' => '2023-06-12']
                ]
            ],

            // 2023/2024 Session
            [
                'name' => '2023/2024',
                'start_date' => '2023-09-11',
                'end_date' => '2024-08-01',
                'is_current' => false,
                'terms' => [
                    [
                        'name' => 'First Term',
                        'start_date' => '2023-09-11',
                        'end_date' => '2023-12-15',
                        'vacation_start' => '2023-12-16',
                        'vacation_end' => '2024-01-07',
                        'midterm_break' => ['2023-10-23', '2023-10-27']
                    ],
                    [
                        'name' => 'Second Term',
                        'start_date' => '2024-01-08',
                        'end_date' => '2024-03-28',
                        'vacation_start' => '2024-03-29',
                        'vacation_end' => '2024-04-21',
                        'midterm_break' => ['2024-02-19', '2024-02-23']
                    ],
                    [
                        'name' => 'Third Term',
                        'start_date' => '2024-04-22',
                        'end_date' => '2024-08-01',
                        'vacation_start' => '2024-08-02',
                        'vacation_end' => '2024-09-08',
                        'midterm_break' => ['2024-06-10', '2024-06-14']
                    ]
                ],
                'holidays' => [
                    ['title' => 'Independence Day', 'date' => '2023-10-01'],
                    ['title' => 'Eid-El Maulud', 'date' => '2023-09-27'],
                    ['title' => 'Christmas', 'date' => '2023-12-25', 'duration' => 2],
                    ['title' => 'Eid-Fitr', 'date' => '2024-04-10', 'duration' => 3],
                    ['title' => 'Good Friday', 'date' => '2024-03-29'],
                    ['title' => 'Easter Monday', 'date' => '2024-04-01'],
                    ['title' => 'Children Day', 'date' => '2024-05-27'],
                    ['title' => 'Eid-El Kabir', 'date' => '2024-06-17', 'duration' => 2],
                    ['title' => 'Democracy Day', 'date' => '2024-06-12']
                ]
            ],

            // 2024/2025 Session (Current)
            [
                'name' => '2024/2025',
                'start_date' => '2024-09-09',
                'end_date' => '2025-09-07',
                'is_current' => true,
                'terms' => [
                    [
                        'name' => 'First Term',
                        'start_date' => '2024-09-09',
                        'end_date' => '2024-12-13',
                        'vacation_start' => '2024-12-14',
                        'vacation_end' => '2025-01-05',
                        'midterm_break' => ['2024-10-21', '2024-10-25']
                    ],
                    [
                        'name' => 'Second Term',
                        'start_date' => '2025-01-06',
                        'end_date' => '2025-03-28',
                        'vacation_start' => '2025-03-29',
                        'vacation_end' => '2025-04-27',
                        'midterm_break' => ['2025-02-17', '2025-02-21']
                    ],
                    [
                        'name' => 'Third Term',
                        'start_date' => '2025-04-28',
                        'end_date' => '2025-08-01',
                        'vacation_start' => '2025-08-02',
                        'vacation_end' => '2025-09-07',
                        'midterm_break' => ['2025-06-09', '2025-06-13']
                    ]
                ],
                'holidays' => [
                    ['title' => 'Independence Day', 'date' => '2024-10-01'],
                    ['title' => 'Eid-El Maulud', 'date' => '2024-09-15'],
                    ['title' => 'Christmas', 'date' => '2024-12-25', 'duration' => 2],
                    ['title' => 'Eid-Fitr', 'date' => '2025-03-31', 'duration' => 3],
                    ['title' => 'Good Friday', 'date' => '2025-04-18'],
                    ['title' => 'Easter Monday', 'date' => '2025-04-21'],
                    ['title' => 'Children Day', 'date' => '2025-05-27'],
                    ['title' => 'Eid-El Kabir', 'date' => '2025-06-07', 'duration' => 2],
                    ['title' => 'Democracy Day', 'date' => '2025-06-12'],
                    ['title' => 'Eid-El Maulud', 'date' => '2025-09-05']
                ]
            ]
        ];
    }
}