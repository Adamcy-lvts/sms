<?php

namespace App\Filament\Sms\Widgets;

use App\Models\Teacher;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TeacherStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $teacher = Teacher::where('staff_id', auth()->user()->staff->id)->first();

        return [
            Stat::make('My Classes', $teacher?->classRooms()->count() ?? 0),
            Stat::make('My Subjects', $teacher?->subjects()->count() ?? 0),
            Stat::make('Total Students', $teacher?->classRooms()->withCount('students')->get()->sum('students_count') ?? 0),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()->hasRole(['teacher', 'class_teacher', 'subject_teacher', 'head_teacher']);
    }
}
