<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Staff;
use App\Models\School;
use App\Models\Status;
use App\Models\Expense;
use App\Models\ExpenseItem;
use Illuminate\Support\Str;
use App\Models\ExpenseCategory;
use App\Services\StatusService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseSeeder extends Seeder
{
    // Define monthly expenses with variance settings
    protected $monthlyExpenses = [
        'Staff Salaries' => [
            'quantity_range' => [1, 1],  // Fixed quantity for salaries
            'variance' => 0.1  // 10% variance for overtime etc
        ],
        'Utilities' => [
            'quantity_range' => [1, 1],
            'variance' => 0.2  // Higher variance for seasonal changes
        ],
        'Internet' => [
            'quantity_range' => [1, 1],
            'variance' => 0.05
        ]
    ];

    // Term-start expenses with typical quantities
    protected $termStartExpenses = [
        'Teaching Materials' => [
            'quantity_range' => [50, 100], // Bulk purchases
            'variance' => 0.15
        ],
        'Maintenance' => [
            'quantity_range' => [1, 3], // Multiple maintenance jobs
            'variance' => 0.25
        ]
    ];

    // Term-end expenses with expected ranges
    protected $termEndExpenses = [
        'Events' => [
            'quantity_range' => [1, 5], // Multiple events possible
            'variance' => 0.3
        ],
        'Cleaning Supplies' => [
            'quantity_range' => [20, 50],
            'variance' => 0.2
        ]
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $school = School::where('slug', 'kings-private-school')->first();

            // Get all categories and expense items
            $categories = ExpenseCategory::where('school_id', $school->id)
                ->with(['expenseItems' => function ($query) {
                    $query->where('is_active', true);
                }])
                ->get()
                ->keyBy('name');

            // Get terms for first two academic sessions
            $terms = $school->terms()
                ->whereHas('academicSession', function ($query) {
                    $query->orderBy('start_date')->limit(2);
                })
                ->with('academicSession')
                ->orderBy('start_date')
                ->get();

            foreach ($terms as $term) {
                $this->generateExpensesForTerm($school, $term, $categories);
            }
        });
    }

    protected function generateExpensesForTerm($school, $term, $categories): void
    {
        $startDate = Carbon::parse($term->start_date);
        $endDate = Carbon::parse($term->end_date);
        $monthsInTerm = $startDate->diffInMonths($endDate) + 1;

        // Generate monthly expenses
        for ($i = 0; $i < $monthsInTerm; $i++) {
            $currentDate = $startDate->copy()->addMonths($i);
            $this->generateMonthlyExpenses($school, $term, $categories, $currentDate);
        }

        // Generate term start and end expenses
        $this->generateTermStartExpenses($school, $term, $categories);
        $this->generateTermEndExpenses($school, $term, $categories);
    }

    protected function generateExpenses($school, $term, $categories, $date, $expenseTypes, $paymentMethod = 'bank_transfer'): void
    {
        foreach ($expenseTypes as $categoryName => $config) {
            if (!isset($categories[$categoryName]) || $categories[$categoryName]->expenseItems->isEmpty()) {
                continue;
            }

            $items = [];
            $totalAmount = 0;

            foreach ($categories[$categoryName]->expenseItems as $item) {
                $quantity = rand($config['quantity_range'][0], $config['quantity_range'][1]);
                $variance = rand(-$config['variance'] * 100, $config['variance'] * 100) / 100;
                $unitPrice = $item->default_amount * (1 + $variance);
                $amount = $quantity * $unitPrice;

                $items[] = [
                    'expense_category_id' => $item->expense_category_id,
                    'expense_item_id' => $item->id,
                    'name' => $item->name, // Added item name
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'unit' => $item->unit,
                    'amount' => $amount,
                    'description' => $item->description
                ];

                $totalAmount += $amount;
            }

            Expense::create([
                'school_id' => $school->id,
                'expense_category_id' => $categories[$categoryName]->id,
                'academic_session_id' => $term->academic_session_id,
                'term_id' => $term->id,
                'expense_items' => $items,
                'amount' => $totalAmount,
                'expense_date' => $date,
                'status' => 'paid',
                'payment_method' => $paymentMethod,
                'description' => "{$categoryName} expenses for " . $date->format('F Y'),
                'reference' => 'EXP-' . strtoupper(Str::random(8)),
                'recorded_by' => 1
            ]);
        }
    }

    protected function generateMonthlyExpenses($school, $term, $categories, $date): void
    {
        $this->generateExpenses($school, $term, $categories, $date, $this->monthlyExpenses);
    }

    protected function generateTermStartExpenses($school, $term, $categories): void
    {
        $startDate = Carbon::parse($term->start_date)->addDays(rand(1, 5));
        $this->generateExpenses($school, $term, $categories, $startDate, $this->termStartExpenses, 'cash');
    }

    protected function generateTermEndExpenses($school, $term, $categories): void
    {
        $endDate = Carbon::parse($term->end_date)->subDays(rand(5, 10));
        $this->generateExpenses($school, $term, $categories, $endDate, $this->termEndExpenses, 'bank_transfer');
    }
}
