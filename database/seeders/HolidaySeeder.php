<?php

namespace Database\Seeders;

use App\Models\School;
use Filament\Facades\Filament;
use Illuminate\Database\Seeder;
use App\Models\SchoolCalendarEvent;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all schools
        $schools = School::all();

        $holidays = [
            [
                'title' => 'New Year\'s Day',
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-01',
                'type' => 'holiday',
                'is_recurring' => true,
                'recurrence_pattern' => 'yearly',
                'excludes_attendance' => true,
            ],
            [
                'title' => 'Good Friday',
                'start_date' => '2024-03-29',
                'end_date' => '2024-03-29',
                'type' => 'holiday',
                'is_recurring' => true,
                'recurrence_pattern' => 'yearly',
                'excludes_attendance' => true,
            ],
            [
                'title' => 'Easter Monday',
                'start_date' => '2024-04-01',
                'end_date' => '2024-04-01',
                'type' => 'holiday',
                'is_recurring' => true,
                'recurrence_pattern' => 'yearly',
                'excludes_attendance' => true,
            ],
            [
                'title' => 'Workers\' Day',
                'start_date' => '2024-05-01',
                'end_date' => '2024-05-01',
                'type' => 'holiday',
                'is_recurring' => true,
                'recurrence_pattern' => 'yearly',
                'excludes_attendance' => true,
            ],
            [
                'title' => 'Democracy Day',
                'start_date' => '2024-06-12',
                'end_date' => '2024-06-12',
                'type' => 'holiday',
                'is_recurring' => true,
                'recurrence_pattern' => 'yearly',
                'excludes_attendance' => true,
            ],
            [
                'title' => 'Independence Day',
                'start_date' => '2024-10-01',
                'end_date' => '2024-10-01',
                'type' => 'holiday',
                'is_recurring' => true,
                'recurrence_pattern' => 'yearly',
                'excludes_attendance' => true,
            ],
            [
                'title' => 'Christmas Day',
                'start_date' => '2024-12-25',
                'end_date' => '2024-12-25',
                'type' => 'holiday',
                'is_recurring' => true,
                'recurrence_pattern' => 'yearly',
                'excludes_attendance' => true,
            ],
            [
                'title' => 'Boxing Day',
                'start_date' => '2024-12-26',
                'end_date' => '2024-12-26',
                'type' => 'holiday',
                'is_recurring' => true,
                'recurrence_pattern' => 'yearly',
                'excludes_attendance' => true,
            ],
        ];

        foreach ($schools as $school) {
            foreach ($holidays as $holiday) {
                $holiday['school_id'] = $school->id;
                SchoolCalendarEvent::create($holiday);
            }
        }
    }
}
