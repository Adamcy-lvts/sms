<?php

namespace App\Filament\Sms\Resources\ClassRoomResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

class ClassRoomStatsOverview extends BaseWidget
{
    public ?Model $record = null;

    /**
     * Get all classroom statistics for the overview widget
     * Auto-refreshed by Filament's polling mechanism
     * 
     * @return array Array of Stat objects
     */
    protected function getStats(): array
    {
        return [
            $this->getTotalStudentsStat(),
            $this->getAttendanceRateStat(),
            $this->getClassAverageStat(),
            $this->getPassRateStat()
        ];
    }

    /**
     * Get total and active student counts
     * Cached for 30 minutes to reduce database load
     */
    protected function getTotalStudentsStat(): Stat
    {
        $cacheKey = "classroom:{$this->record->id}:total_students";

        // Cache student counts with eager-loaded status relationship
        $stats = Cache::remember($cacheKey, now()->addMinutes(30), function () {
            $students = $this->record->students;
            return [
                'total' => $students->count(),
                'active' => $students->where('status.name', 'active')->count()
            ];
        });

        return Stat::make('Total Students', $stats['total'])
            ->description("{$stats['active']} active")
            ->color('success');
    }

    /**
     * Calculate attendance rate for current term
     * Cached hourly since attendance changes frequently
     */
    protected function getAttendanceRateStat(): Stat
    {
        $cacheKey = "classroom:{$this->record->id}:attendance_rate";

        $attendanceRate = Cache::remember($cacheKey, now()->addHour(), function () {
            // Get counts for current academic period
            if (!config('app.current_session') || !config('app.current_term')) {
                return true;
            }
            $presentCount = \App\Models\AttendanceRecord::where([
                'class_room_id' => $this->record->id,
                'academic_session_id' => config('app.current_session')->id,
                'term_id' => config('app.current_term')->id,
                'status' => 'present'
            ])->count();

            $totalCount = \App\Models\AttendanceRecord::where([
                'class_room_id' => $this->record->id,
                'academic_session_id' => config('app.current_session')->id,
                'term_id' => config('app.current_term')->id,
            ])->count();

            return $totalCount > 0 ? ($presentCount / $totalCount) * 100 : 0;
        });

        return Stat::make('Attendance Rate', number_format($attendanceRate, 1) . '%')
            ->description('Current Term')
            ->color($attendanceRate >= 75 ? 'success' : 'danger');
    }

    /**
     * Calculate class average from all student grades
     * Cached for 6 hours since grades change less frequently
     */
    protected function getClassAverageStat(): Stat
    {
        $cacheKey = "classroom:{$this->record->id}:class_average";

        $average = Cache::remember($cacheKey, now()->addHours(6), function () {
            return \App\Models\StudentGrade::whereHas('student', function ($query) {
                $query->where('class_room_id', $this->record->id);
            })->avg('score') ?? 0;
        });

        return Stat::make('Class Average', number_format($average, 1) . '%')
            ->description('Overall Performance')
            ->color($average >= 75 ? 'success' : 'warning');
    }

    /**
     * Calculate percentage of passing grades (>=50%)
     * Cached for 6 hours along with class average
     */
    protected function getPassRateStat(): Stat
    {
        $cacheKey = "classroom:{$this->record->id}:pass_rate";

        $passRate = Cache::remember($cacheKey, now()->addHours(6), function () {
            $grades = \App\Models\StudentGrade::whereHas('student', function ($query) {
                $query->where('class_room_id', $this->record->id);
            })->get();

            $passing = $grades->where('score', '>=', 50)->count();
            $total = $grades->count();

            return $total > 0 ? ($passing / $total) * 100 : 0;
        });

        return Stat::make('Pass Rate', number_format($passRate, 1) . '%')
            ->description('Students scoring 50% or above')
            ->color($passRate >= 70 ? 'success' : 'warning');
    }

    /**
     * Invalidate all cached stats for a classroom
     * Call this method when:
     * - Students are added/removed
     * - Attendance is marked
     * - Grades are updated
     * 
     * @param int $classroomId
     */
    public static function invalidateCache($classroomId): void
    {
        $keys = [
            "classroom:{$classroomId}:total_students",
            "classroom:{$classroomId}:attendance_rate",
            "classroom:{$classroomId}:class_average",
            "classroom:{$classroomId}:pass_rate"
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}
