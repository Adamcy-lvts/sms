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
            // Current term financial performance
            Stat::make('Term Revenue', $this->getCurrentTermRevenue())
                ->description($this->getRevenueComparison()) // Term-over-term comparison
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart($this->getRevenueChart()) // 7-day revenue trend
                ->color('success'),

            // Lifetime revenue tracking
            Stat::make('Term Expenses', $this->getCurrentTermExpenses())
                ->description($this->getCurrentTermExpenseComparison())
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('warning'),

            Stat::make('All-Time Revenue', $this->getAllTimeRevenue())
                ->description('Total collections to date')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            // Expense monitoring
            Stat::make('All-Time Expenses', $this->getAllTimeExpenses())
                ->description('Total expenses to date')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('danger'),
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

    protected function getFinancialStats(): string
    {
        $paidStatusId = Status::where('type', 'payment')
            ->where('name', 'paid')
            ->value('id');

        $revenue = Payment::where('school_id', Filament::getTenant()->id)
            ->where('academic_session_id', config('app.current_session')->id)
            ->where('term_id', config('app.current_term')->id)
            ->where('status_id', $paidStatusId)
            ->sum('deposit');

        return formatNaira($revenue);
    }

    protected function getPaymentSummary(): string
    {
        $outstanding = Payment::where('school_id', Filament::getTenant()->id)
            ->where('academic_session_id', config('app.current_session')->id)
            ->where('term_id', config('app.current_term')->id)
            ->sum('balance');

        return 'Outstanding: ' . formatNaira($outstanding);
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

    protected function getCurrentTermRevenue(): string
    {
        $paidStatusId = Status::where('type', 'payment')
            ->where('name', 'paid')
            ->value('id');

        $revenue = Payment::where('school_id', Filament::getTenant()->id)
            ->where('academic_session_id', config('app.current_session')->id ?? null)
            ->where('term_id', config('app.current_term')->id ?? null)
            ->where('status_id', $paidStatusId)
            ->sum('deposit');

        return formatNaira($revenue);
    }

    protected function getAllTimeRevenue(): string
    {
        $paidStatusId = Status::where('type', 'payment')
            ->where('name', 'paid')
            ->value('id');

        $revenue = Payment::where('school_id', Filament::getTenant()->id)
            ->where('status_id', $paidStatusId)
            ->sum('deposit');

        return formatNaira($revenue);
    }

    protected function getCurrentTermExpenses(): string
    {
        $expenses = Expense::where('school_id', Filament::getTenant()->id)
            ->where('academic_session_id', config('app.current_session')->id ?? null)
            ->where('term_id', config('app.current_term')->id ?? null)
            ->where('status', 'approved')
            ->sum('amount');

        return formatNaira($expenses);
    }

    protected function getAllTimeExpenses(): string
    {
        $expenses = Expense::where('school_id', Filament::getTenant()->id)
            ->where('status', 'approved')
            ->sum('amount');

        return formatNaira($expenses);
    }

    protected function getRevenueComparison(): string
    {
        $currentTerm = config('app.current_term') ?? null;
        $currentSession = config('app.current_session') ?? null;

        if (!$currentTerm || !$currentSession) {
            return 'No term data';
        }

        $tenant = Filament::getTenant();

        $currentTermRevenue = Payment::where('school_id', $tenant->id)
            ->where('term_id', $currentTerm->id ?? null)
            ->where('academic_session_id', $currentSession->id ?? null)
            ->sum('deposit');

        $previousTerm = Term::where('school_id', $tenant->id)
            ->where('start_date', '<', $currentTerm->start_date)
            ->orderBy('start_date', 'desc')
            ->first();

        $lastTermRevenue = 0;
        if ($previousTerm) {
            $lastTermRevenue = Payment::where('school_id', $tenant->id)
                ->where('term_id', $previousTerm->id ?? null)
                ->where('academic_session_id', $previousTerm->academic_session_id ?? null)
                ->sum('deposit');
        }

        $percentChange = $lastTermRevenue > 0
            ? (($currentTermRevenue - $lastTermRevenue) / $lastTermRevenue) * 100
            : 0;

        // Format comparison text based on whether terms are in same session
        if ($previousTerm && $previousTerm->academic_session_id === $currentSession->id ?? null) {
            return sprintf(
                '%s%s%% vs %s',
                ($percentChange >= 0 ? '+' : ''),
                number_format($percentChange, 1),
                $previousTerm->name
            );
        }

        // If different session, show shortened year
        $shortSession = '';
        if ($previousTerm?->academicSession) {
            $years = explode('/', $previousTerm->academicSession->name);
            $shortSession = substr($years[0], -2) . '/' . substr($years[1], -2);
            return sprintf(
                '%s%s%% vs %s (%s)',
                ($percentChange >= 0 ? '+' : ''),
                number_format($percentChange, 1),
                $previousTerm->name,
                $shortSession
            );
        }

        return sprintf(
            '%s%s%% vs Previous Term',
            ($percentChange >= 0 ? '+' : ''),
            number_format($percentChange, 1)
        );
    }

    protected function getCurrentTermExpenseComparison(): string
    {
        $currentTerm = config('app.current_term');
        $currentSession = config('app.current_session');

        if (!$currentTerm || !$currentSession) {
            return 'No term data';
        }

        $tenant = Filament::getTenant();

        $currentTermExpenses = Expense::where('school_id', $tenant->id)
            ->where('term_id', $currentTerm->id )
            ->where('academic_session_id', $currentSession->id)
            ->where('status', 'approved')
            ->sum('amount');

        $previousTerm = Term::where('school_id', $tenant->id)
            ->where('start_date', '<', $currentTerm->start_date ?? null)
            ->orderBy('start_date', 'desc')
            ->first();

        $lastTermExpenses = 0;
        if ($previousTerm) {
            $lastTermExpenses = Expense::where('school_id', $tenant->id)
                ->where('term_id', $previousTerm->id ?? null)
                ->where('academic_session_id', $previousTerm->academic_session_id ?? null)
                ->where('status', 'approved')
                ->sum('amount');
        }

        $percentChange = $lastTermExpenses > 0
            ? (($currentTermExpenses - $lastTermExpenses) / $lastTermExpenses) * 100
            : 0;

        // Format comparison text
        if ($previousTerm) {
            return sprintf(
                '%s%s%% vs %s',
                ($percentChange >= 0 ? '+' : ''),
                number_format($percentChange, 1),
                $previousTerm->name
            );
        }

        return 'No previous term data';
    }

    protected function getExpenseComparison(): string
    {
        $totalExpenses = Expense::where('school_id', Filament::getTenant()->id)
            ->where('status', 'approved')
            ->sum('amount');

        if ($totalExpenses === 0) {
            return 'No expenses recorded';
        }

        $currentTermExpenses = Expense::where('school_id', Filament::getTenant()->id)
            ->where('academic_session_id', config('app.current_session')->id)
            ->where('term_id', config('app.current_term')->id)
            ->where('status', 'approved')
            ->sum('amount');

        return 'Total: ' . formatNaira($totalExpenses);
    }

    protected function getExpenseTrend(): string
    {
        $currentYear = now()->year;
        $lastYear = $currentYear - 1;

        $currentYearExpenses = Expense::where('school_id', Filament::getTenant()->id)
            ->where('status', 'approved')
            ->whereYear('expense_date', $currentYear)
            ->sum('amount');

        $lastYearExpenses = Expense::where('school_id', Filament::getTenant()->id)
            ->where('status', 'approved')
            ->whereYear('expense_date', $lastYear)
            ->sum('amount');

        $percentChange = $lastYearExpenses > 0
            ? (($currentYearExpenses - $lastYearExpenses) / $lastYearExpenses) * 100
            : 0;

        return ($percentChange >= 0 ? '+' : '') . number_format($percentChange, 1) . '% vs last year';
    }

    protected function getFinancialHealthColor(): string
    {
        $totalExpected = Payment::where('school_id', Filament::getTenant()->id)
            ->where('academic_session_id', config('app.current_session')->id ?? null)
            ->where('term_id', config('app.current_term')->id   ?? null)
            ->sum('amount');

        $collected = Payment::where('school_id', Filament::getTenant()->id)
            ->where('academic_session_id', config('app.current_session')->id ?? null)
            ->where('term_id', config('app.current_term')->id  ?? null)
            ->sum('deposit');

        $collectionRate = $totalExpected > 0 ? ($collected / $totalExpected) : 0;

        return match (true) {
            $collectionRate >= 0.8 => 'success',
            $collectionRate >= 0.5 => 'warning',
            default => 'danger'
        };
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

    protected function getRevenueChart(): array
    {
        // Get last 7 days revenue trend
        return Payment::where('school_id', Filament::getTenant()->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get([
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(deposit) as total')
            ])
            ->pluck('total')
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
