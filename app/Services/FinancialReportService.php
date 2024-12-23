<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Term;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\ClassRoom;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FinancialReportService
{
    // Cache duration - 1 hour by default
    protected const CACHE_TTL = 3600;

    /**
     * Generate a financial report for a specific period
     *
     * @param string $periodType 'month', 'term', or 'session'
     * @param mixed $periodId Specific period identifier
     * @param array $options Additional options for report generation
     * @return array
     */
    public function generateReport(string $periodType, mixed $periodId, array $options = []): array
    {
        // Generate cache key based on parameters
        $cacheKey = $this->generateCacheKey($periodType, $periodId, $options);

        // Try to get from cache first
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($periodType, $periodId, $options) {
            return $this->computeReport($periodType, $periodId, $options);
        });
    }

    /**
     * Compute the actual report data
     */
    // In FinancialReportService.php

    protected function computeReport(string $periodType, mixed $periodId, array $options): array
    {
        $dateRange = $this->getDateRange($periodType, $periodId);
        $income = $this->calculateIncome($dateRange);
        $expenses = $this->calculateExpenses($dateRange);

        // Get period details
        $periodDetails = match ($periodType) {
            'term' => [
                'type' => 'term',
                'term_name' => Term::find($periodId)?->name,
            ],
            'session' => [
                'type' => 'session',
                'session_name' => AcademicSession::find($periodId)?->name,
            ],
            default => [
                'type' => 'month',
                'month_name' => Carbon::parse($periodId)->format('F Y'),
            ],
        };

        return [
            'period' => array_merge($periodDetails, [
                'start_date' => $dateRange['start'],
                'end_date' => $dateRange['end'],
            ]),
            'summary' => [
                'total_income' => $income['total'],
                'total_expenses' => $expenses['total'],
                'net_profit' => $income['total'] - $expenses['total'],
                'profit_margin' => $income['total'] > 0
                    ? (($income['total'] - $expenses['total']) / $income['total']) * 100
                    : 0,
            ],
            'income' => $income,
            'expenses' => $expenses,
        ];
    }


    protected function calculateIncome(array $dateRange): array
    {


        $payments = Payment::whereBetween('paid_at', [
            $dateRange['start'],
            $dateRange['end']
        ])
            ->with(['paymentItems.paymentType', 'student', 'status', 'paymentMethod'])
            ->get();


        $total = $payments->sum('amount');
        $byType = [];

        foreach ($payments as $payment) {
            foreach ($payment->paymentItems as $item) {
                if ($item->paymentType) {
                    $typeName = $item->paymentType->name;

                    if (!isset($byType[$typeName])) {
                        $byType[$typeName] = [
                            'amount' => 0,
                            'count' => 0,
                            'name' => $typeName,
                            'students' => [], // Track unique students
                            'student_count' => 0
                        ];
                    }

                    $byType[$typeName]['amount'] += $item->amount;
                    $byType[$typeName]['count']++;

                    // Add student ID to track unique students
                    if ($payment->student_id && !in_array($payment->student_id, $byType[$typeName]['students'])) {
                        $byType[$typeName]['students'][] = $payment->student_id;
                        $byType[$typeName]['student_count']++;
                    }
                }
            }
        }

        // Remove the students array from final output
        foreach ($byType as &$type) {
            unset($type['students']);
        }

        $days = max(1, $dateRange['days'] ?? 1);

        return [
            'status_breakdown' => [
                'paid' => $payments->where('status.name', 'paid')->count(),
                'partial' => $payments->where('status.name', 'partial')->count(),
                'pending' => $payments->where('status.name', 'pending')->count()
            ],
            'class_breakdown' => $payments->groupBy('class_room_id')
                ->map(fn($payments, $classId) => [
                    'name' => ClassRoom::find($classId)?->name,
                    'amount' => $payments->sum('amount'),
                    'student_count' => $payments->unique('student_id')->count()
                ])->values(),
            // Add to each type array
            'by_type' => collect($byType)->map(function ($type) use ($payments) {
                return $type;
            })->values()->toArray(),
            'total' => $total,
            'daily_average' => $total / max(1, $dateRange['days'] ?? 1),
            'transactions' => $payments->count(),
            'total_students' => collect($payments)->unique('student_id')->count(),
            'payment_methods' => $payments
                ->groupBy('payment_method.name')
                ->map(fn($group) => [
                    'amount' => $group->sum('amount'),
                    'count' => $group->count(),
                    'average' => $group->avg('amount')
                ]),
            'collection_metrics' => [
                'on_time_payments' => $payments->where('paid_at', '<=', 'due_date')->count(),
                'late_payments' => $payments->where('paid_at', '>', 'due_date')->count(),
                'average_delay' => $payments
                    ->where('paid_at', '>', 'due_date')
                    ->avg(fn($p) => $p->paid_at->diffInDays($p->due_date))
            ],
            'monthly_trends' => $payments
                ->groupBy(function ($p) {
                    return Carbon::parse($p->paid_at)->format('M Y');
                })
                ->map(function ($group) {
                    return [
                        'income' => $group->sum('amount'),
                        'student_count' => $group->unique('student_id')->count()
                    ];
                }),
            'weekday_analysis' => $payments
                ->groupBy(function ($p) {
                    return Carbon::parse($p->paid_at)->format('l');
                })
                ->map(fn($group) => $group->sum('amount')),
            'peak_collection_hours' => $payments
                ->groupBy(function ($p) {
                    return Carbon::parse($p->paid_at)->format('H');
                })
                ->map(fn($group) => $group->count()),

            // 'time_analysis' => $this->getTimeAnalysis($payment)
        ];
    }

    // Add to FinancialReportService
    protected function getTimeAnalysis($payments)
    {
        return [
            'monthly_trends' => $payments
                ->groupBy(function ($p) {
                    return Carbon::parse($p->paid_at)->format('M Y');
                })
                ->map(function ($group) {
                    return [
                        'income' => $group->sum('amount'),
                        'student_count' => $group->unique('student_id')->count()
                    ];
                }),
            'weekday_analysis' => $payments
                ->groupBy(function ($p) {
                    return Carbon::parse($p->paid_at)->format('l');
                })
                ->map(fn($group) => $group->sum('amount')),
            'peak_collection_hours' => $payments
                ->groupBy(function ($p) {
                    return Carbon::parse($p->paid_at)->format('H');
                })
                ->map(fn($group) => $group->count())
        ];
    }

    protected function calculateExpenses(array $dateRange): array
    {
        $expenseQuery = Expense::with('category');

        // Handle both session and term periods
        if (isset($dateRange['academic_session_id'])) {
            // Session-based query
            $expenseQuery->where('academic_session_id', $dateRange['academic_session_id']);
            if (isset($dateRange['term_id'])) {
                // Add term filter if specified
                $expenseQuery->where('term_id', $dateRange['term_id']);
            }
        } else {
            // Date range-based query
            $expenseQuery->whereBetween('expense_date', [
                $dateRange['start'],
                $dateRange['end']
            ]);
        }

        $expenses = $expenseQuery->get();

        // Rest of your existing expense calculation logic
        $byCategory = $expenses->groupBy(function ($expense) {
            return $expense->category ? $expense->category->name : 'Uncategorized';
        })->map(function ($categoryExpenses) {
            $items = collect();
            foreach ($categoryExpenses as $expense) {
                if (!empty($expense->expense_items)) {
                    foreach ($expense->expense_items as $item) {
                        $items->push([
                            'name' => $item['name'] ?? 'Unknown Item',
                            'quantity' => $item['quantity'] ?? 1,
                            'unit' => $item['unit'] ?? 'unit',
                            'unit_price' => $item['unit_price'] ?? ($item['amount'] ?? 0),
                            'amount' => $item['amount'] ?? 0,
                        ]);
                    }
                }
            }

            return [
                'total' => $categoryExpenses->sum('amount'),
                'count' => $categoryExpenses->count(),
                'items' => $items->toArray()
            ];
        });

        return [
            'total' => $expenses->sum('amount'),
            'by_category' => $byCategory,
            'daily_average' => $expenses->sum('amount') / $dateRange['days'],
            'transactions' => $expenses->count(),
        ];
    }
    /**
     * Get category-wise breakdown of income and expenses
     */
    protected function getCategoryBreakdown(array $dateRange): array
    {
        // Implementation details...
        return [
            'income_categories' => [],
            'expense_categories' => [],
            'profit_by_category' => []
        ];
    }

    /**
     * Calculate financial trends over the period
     */
    protected function calculateTrends(array $dateRange): array
    {
        // Implementation for trend analysis...
        return [
            'daily_totals' => [],
            'weekly_averages' => [],
            'growth_rate' => 0
        ];
    }

    /**
     * Generate a cache key for the report
     */
    protected function generateCacheKey(string $periodType, mixed $periodId, array $options): string
    {
        return "financial_report:{$periodType}:{$periodId}:" . md5(serialize($options));
    }

    /**
     * Get date range based on period type and ID
     */
    protected function getDateRange(string $periodType, mixed $periodId): array
    {
        return match ($periodType) {
            'month' => [
                'start' => Carbon::parse($periodId)->startOfMonth(),
                'end' => Carbon::parse($periodId)->endOfMonth(),
                'days' => Carbon::parse($periodId)->daysInMonth,
            ],
            'term' => $this->getTermDateRange($periodId),
            'session' => $this->getSessionDateRange($periodId),
            default => throw new \InvalidArgumentException('Invalid period type'),
        };
    }



    protected function getTermDateRange(int $termId): array
    {
        $term = Term::findOrFail($termId);
        $startDate = Carbon::parse($term->start_date);
        $endDate = Carbon::parse($term->end_date);

        return [
            'start' => $startDate,
            'end' => $endDate,
            'days' => $startDate->diffInDays($endDate) + 1,
            'term_id' => $term->id,
            'academic_session_id' => $term->academic_session_id
        ];
    }

    protected function getSessionDateRange(int $sessionId): array
    {
        $session = AcademicSession::findOrFail($sessionId);
        $startDate = Carbon::parse($session->start_date);
        $endDate = Carbon::parse($session->end_date);

        return [
            'start' => $startDate,
            'end' => $endDate,
            'days' => $startDate->diffInDays($endDate) + 1,
            'academic_session_id' => $session->id
        ];
    }
}
