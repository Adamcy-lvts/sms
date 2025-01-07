<?php

namespace App\Filament\Sms\Widgets;

use Carbon\Carbon;
use App\Models\Term;
use App\Models\Status;
use App\Models\Payment;
use Filament\Support\RawJs;
use Filament\Facades\Filament;
use App\Models\AcademicSession;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class RevenueBarChart extends ChartWidget
{
    use HasWidgetShield;
    
    protected static ?string $heading = 'Revenue Analysis';
    protected static ?int $sort = 1;
    // protected int | string | array $columnSpan = 'full';
    // protected static ?string $maxHeight = '300px';
    // Initialize filter with current term
    public ?string $filter = null;

    public function mount(): void
    {
        if (!$this->filter) {
            $this->filter = $this->getCurrentTermFilterKey();
        }
    }

    protected function getCurrentTermFilterKey(): string
    {
        $currentSession = config('app.current_session');
        $currentTerm = config('app.current_term');

        return "term_" . ($currentSession?->id ?? '0') . "_" . ($currentTerm?->id ?? '0');
    }

    protected function getFilters(): ?array
    {
        return [
            'daily' => 'Last 30 Days',
            'weekly' => 'Last 4 Weeks',
            'monthly' => 'Last 12 Months',
            'yearly' => 'This Year',
            // Add session-based filters dynamically
            ...$this->getSessionFilters(),
        ];
    }

    protected function getSessionFilters(): array
    {
        $tenant = Filament::getTenant();
        if (!$tenant) {
            return [];
        }

        $filters = [];

        // Get sessions
        $sessions = AcademicSession::where('school_id', $tenant->id)
            ->orderByDesc('start_date')
            ->get();

        // Add session filters
        foreach ($sessions as $session) {
            $filters["session_{$session->id}"] = $session->name;
            
            // Get terms for this session
            $terms = Term::where('school_id', $tenant->id)
                ->where('academic_session_id', $session->id)
                ->orderBy('id')
                ->get();

            // Add term filters
            foreach ($terms as $term) {
                $filters["term_{$session->id}_{$term->id}"] = "{$session->name} - {$term->name}";
            }
        }

        return $filters;
    }

    public function gettype(): string
    {
        return 'bar';
    }

    protected function getColorPalette(): array
    {
        return [
            '#10B981', // Emerald
            '#3B82F6', // Blue
            '#F59E0B', // Amber
            '#8B5CF6', // Purple
            '#EC4899', // Pink
        ];
    }

    protected function getData(): array
    {
        $tenant = Filament::getTenant();
        if (!$tenant) {
            return $this->getEmptyDataStructure();
        }

        $paidStatusId = Status::where('type', 'payment')
            ->where('name', 'paid')
            ->value('id');

        if (!$paidStatusId) {
            return $this->getEmptyDataStructure();
        }

        $query = Payment::where('school_id', $tenant->id)
            ->where('status_id', $paidStatusId);

        // Handle session and term filters
        if (str_starts_with($this->filter ?? '', 'session_')) {
            $sessionId = substr($this->filter, 8);
            return $this->getSessionData($query, $sessionId);
        }

        if (str_starts_with($this->filter ?? '', 'term_')) {
            [, $sessionId, $termId] = array_pad(explode('_', $this->filter ?? ''), 3, null);
            if ($sessionId && $termId) {
                return $this->getTermData($query, $sessionId, $termId);
            }
        }

        // Handle time-based filters
        return match ($this->filter) {
            'daily' => $this->getDailyData($query),
            'weekly' => $this->getWeeklyData($query),
            'yearly' => $this->getYearlyData($query),
            default => $this->getMonthlyData($query),
        };
    }

    protected function getEmptyDataStructure(): array
    {
        return [
            'datasets' => [[
                'label' => 'Revenue',
                'data' => [],
                'backgroundColor' => $this->getColorPalette()[0],
            ]],
            'labels' => [],
        ];
    }

    protected function getSessionData($query, $sessionId): array
    {
        $session = AcademicSession::find($sessionId);
        if (!$session) {
            return $this->getEmptyDataStructure();
        }

        $data = $query->where('academic_session_id', $sessionId)
            ->select('term_id')
            ->selectRaw('COALESCE(SUM(deposit), 0) as total')
            ->groupBy('term_id')
            ->orderBy('term_id')
            ->get();

        return [
            'datasets' => [[
                'label' => 'Revenue',
                'data' => $data->pluck('total')->map(fn($value) => $value ?? 0)->toArray(),
                'backgroundColor' => $this->getColorPalette(),
            ]],
            'labels' => $data->map(function ($item) {
                return Term::find($item->term_id)?->name ?? 'Unknown Term';
            })->toArray(),
        ];
    }

    protected function getTermData($query, $sessionId, $termId): array
    {
        $data = $query->where('academic_session_id', $sessionId)
            ->where('term_id', $termId)
            ->select(DB::raw('DATE(paid_at) as date'))
            ->selectRaw('SUM(deposit) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [[
                'label' => 'Revenue',
                'data' => $data->pluck('total')->toArray(),
                'backgroundColor' => $this->getColorPalette()[0], // Single color for term view
            ]],
            'labels' => $data->map(fn($item) => Carbon::parse($item->date)->format('d M'))->toArray(),
        ];
    }

    protected function getDailyData($query): array
    {
        $endDate = now()->endOfDay();
        $startDate = now()->subDays(29)->startOfDay();

        // Get actual data
        $actualData = $query->whereBetween('paid_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(paid_at) as date'))
            ->selectRaw('SUM(deposit) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        // Generate date range and merge with actual data
        $dates = [];
        $totals = [];
        
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dateKey = $date->format('Y-m-d');
            $dates[] = $date->format('d M');
            $totals[] = $actualData[$dateKey] ?? 0;
        }

        return [
            'datasets' => [[
                'label' => 'Revenue',
                'data' => array_reverse($totals),
                'backgroundColor' => $this->getColorPalette()[0], // Single color for daily view
            ]],
            'labels' => array_reverse($dates),
        ];
    }

    protected function getWeeklyData($query): array
    {
        $endDate = now()->endOfWeek();
        $startDate = now()->subWeeks(3)->startOfWeek();

        // Get actual data
        $actualData = $query->whereBetween('paid_at', [$startDate, $endDate])
            ->select(DB::raw('YEARWEEK(paid_at) as week'))
            ->selectRaw('SUM(deposit) as total')
            ->groupBy('week')
            ->orderBy('week')
            ->pluck('total', 'week')
            ->toArray();

        // Generate weeks range and merge with actual data
        $weeks = [];
        $totals = [];

        for ($date = $startDate->copy(); $date <= $endDate; $date->addWeek()) {
            $weekKey = $date->format('YW');
            $weekLabel = $date->format('d M') . ' - ' . $date->endOfWeek()->format('d M');
            $weeks[] = $weekLabel;
            $totals[] = $actualData[$weekKey] ?? 0;
        }

        return [
            'datasets' => [[
                'label' => 'Revenue',
                'data' => array_reverse($totals),
                'backgroundColor' => $this->getColorPalette(), // Rotate through colors for weeks
            ]],
            'labels' => array_reverse($weeks),
        ];
    }

    protected function getMonthlyData($query): array
    {
        $endDate = now()->endOfMonth();
        $startDate = now()->subMonths(11)->startOfMonth();

        // Get actual data
        $actualData = $query->whereBetween('paid_at', [$startDate, $endDate])
            ->select(DB::raw('DATE_FORMAT(paid_at, "%Y-%m") as month'))
            ->selectRaw('SUM(deposit) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Generate months range and merge with actual data
        $months = [];
        $totals = [];

        for ($date = $startDate->copy(); $date <= $endDate; $date->addMonth()) {
            $monthKey = $date->format('Y-m');
            $months[] = $date->format('M Y');
            $totals[] = $actualData[$monthKey] ?? 0;
        }

        return [
            'datasets' => [[
                'label' => 'Revenue',
                'data' => array_reverse($totals),
                'backgroundColor' => $this->getColorPalette()[0], // Single color for monthly view
            ]],
            'labels' => array_reverse($months),
        ];
    }

    protected function getYearlyData($query): array
    {
        $currentYear = now()->year;
        
        // Get actual data
        $actualData = $query->whereYear('paid_at', $currentYear)
            ->select(DB::raw('MONTH(paid_at) as month'))
            ->selectRaw('SUM(deposit) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Generate all months and merge with actual data
        $months = [];
        $totals = [];

        for ($month = 1; $month <= 12; $month++) {
            $date = now()->setMonth($month)->startOfMonth();
            $months[] = $date->format('M');
            $totals[] = $actualData[$month] ?? 0;
        }

        return [
            'datasets' => [[
                'label' => 'Revenue',
                'data' => $totals,
                'backgroundColor' => $this->getColorPalette(), // Rotate through colors for months
            ]],
            'labels' => $months,
        ];
    }

    public function getDescription(): ?string
    {
        if (!$this->filter) {
            return 'Revenue analysis over time';
        }

        if (str_starts_with($this->filter, 'session_')) {
            $sessionId = substr($this->filter, 8);
            $session = AcademicSession::find($sessionId);
            return "Revenue breakdown by term for " . ($session?->name ?? 'Unknown Session');
        }

        if (str_starts_with($this->filter, 'term_')) {
            [, $sessionId, $termId] = array_pad(explode('_', $this->filter), 3, null);
            $session = AcademicSession::find($sessionId);
            $term = Term::find($termId);
            return "Daily revenue for " . ($term?->name ?? 'Unknown Term') . 
                   " (" . ($session?->name ?? 'Unknown Session') . ")";
        }

        // Handle time-based filters
        return match ($this->filter) {
            'daily' => 'Revenue collected over the last 30 days',
            'weekly' => 'Weekly revenue for the last 4 weeks',
            'monthly' => 'Monthly revenue trend for the last 12 months',
            'yearly' => 'Monthly revenue breakdown for ' . now()->year,
            default => 'Revenue analysis over time',
        };
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
        {
            scales: {
                y: {
                    ticks: {
                        callback: (value) => '₦' + value,
                    },
                },
            },
        }
    JS);
    }
}
