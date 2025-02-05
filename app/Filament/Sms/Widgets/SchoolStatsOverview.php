<?php

namespace App\Filament\Sms\Widgets;

use App\Models\Term;
use App\Models\Staff;
use App\Models\Status;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\ClassRoom;
use App\Models\ReportCard;
use App\Models\PaymentItem;
use Filament\Facades\Filament;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget\Stat;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class SchoolStatsOverview extends BaseWidget
{
    use HasWidgetShield;
    
    protected static ?string $pollingInterval = '30s';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 0;

    // Set max columns to 4
    protected function getColumns(): int
    {
        return 4;
    }

    // Population & Academic Stats Group
    protected function getStats(): array
    {
        // Get current school context
        $tenant = Filament::getTenant();
        $currentSession = config('app.current_session');
        $currentTerm = config('app.current_term');

        return [
            // ROW 1: Population & Academic Stats (4)
            Stat::make('Active Students', $this->getActiveStudentStats())
                ->description('Current student population')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->chart($this->getClassDistribution()) // Visual representation of student distribution
                ->color('success'),

            // Staff metrics including all active employees
            Stat::make('Active Staff', $this->getStaffStats())
                ->description('Total school staff')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            // ACADEMIC METRICS GROUP
            // Subject tracking with active class usage
            Stat::make('Total Subjects', $this->getTotalSubjects())
                ->description($this->getSubjectSummary())
                ->descriptionIcon('heroicon-m-book-open')
                ->color('success'),

            // Class tracking with average class size
            Stat::make('Total Classes', $this->getClassStats())
                ->description($this->getClassSummary() . ' students per class')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary'),

            // ROW 2: Financial Stats (4)
  
        ];
    }

    protected function getActiveStudentStats(): string
    {
        // Cache for 5 minutes to reduce database load
        return cache()->remember('active-students-' . Filament::getTenant()->id, 300, function () {
            $activeStatusId = Status::where('type', 'student')
                ->where('name', 'active')
                ->value('id');

            return number_format(Student::where('school_id', Filament::getTenant()->id)
                ->where('status_id', $activeStatusId)
                ->count());
        });
    }

    protected function getClassStats(): string
    {
        $totalClasses = ClassRoom::where('school_id', Filament::getTenant()->id)
            ->count();

        return number_format($totalClasses);
    }

    protected function getClassSummary(): string
    {
        $activeStatusId = Status::where('type', 'student')
            ->where('name', 'active')
            ->value('id');

        $totalStudents = Student::where('school_id', Filament::getTenant()->id)
            ->where('status_id', $activeStatusId)
            ->count();

        $totalClasses = ClassRoom::where('school_id', Filament::getTenant()->id)
            ->count();

        $averageClassSize = $totalClasses > 0 ? ($totalStudents / $totalClasses) : 0;

        return 'Avg. size: ' . number_format($averageClassSize, 0);
    }

    protected function getClassSizeDistribution(): array
    {
        $activeStatusId = Status::where('type', 'student')
            ->where('name', 'active')
            ->value('id');

        return DB::table('class_rooms')
            ->select('class_rooms.name')
            ->selectRaw('COALESCE(COUNT(students.id), 0) as count')
            ->leftJoin('students', function ($join) use ($activeStatusId) {
                $join->on('class_rooms.id', '=', 'students.class_room_id')
                    ->where('students.status_id', $activeStatusId);
            })
            ->where('class_rooms.school_id', Filament::getTenant()->id)
            ->groupBy('class_rooms.id', 'class_rooms.name')
            ->orderBy('class_rooms.name')
            ->pluck('count')
            ->toArray();
    }

    protected function getStaffStats(): string
    {
        $activeStatusId = Status::where('type', 'staff')
            ->where('name', 'active')
            ->value('id');

        $activeStaff = Staff::where('school_id', Filament::getTenant()->id)
            ->where('status_id', $activeStatusId)
            ->count();

        return number_format($activeStaff);
    }



    protected function getTotalSubjects(): string
    {
        $totalSubjects = Subject::where('school_id', Filament::getTenant()->id)
            ->where('is_active', true)
            ->count();

        return number_format($totalSubjects);
    }

    protected function getSubjectSummary(): string
    {
        $totalSubjectsWithClasses = Subject::where('school_id', Filament::getTenant()->id)
            ->whereHas('classRooms')
            ->count();

        return 'Active in classes: ' . number_format($totalSubjectsWithClasses);
    }



    protected function getClassDistribution(): array
    {
        return ClassRoom::where('school_id', Filament::getTenant()->id)
            ->withCount(['students' => function ($query) {
                $query->where('status_id', 1); // Assuming 1 is active status
            }])
            ->pluck('students_count')
            ->toArray();
    }

  

    protected function getWarningColor($outstanding, $revenue): string
    {
        $ratio = $outstanding / ($revenue ?: 1);
        return match (true) {
            $ratio > 0.5 => 'danger',
            $ratio > 0.3 => 'warning',
            default => 'info'
        };
    }
}
