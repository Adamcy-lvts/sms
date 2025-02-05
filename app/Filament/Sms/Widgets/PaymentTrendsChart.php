<?php

namespace App\Filament\Sms\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Filament\Facades\Filament;

class PaymentTrendsChart extends ChartWidget
{
    protected static ?int $sort = 4;
    protected static ?string $heading = 'Payment Trends';
    protected static ?string $maxHeight = '380px';
    // protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $payments = Payment::where('school_id', Filament::getTenant()->id)
            ->whereNotNull('paid_at')
            ->whereYear('paid_at', now()->year)
            ->selectRaw('MONTH(paid_at) as month_num, MONTHNAME(paid_at) as month, SUM(deposit) as total')
            ->groupBy('month_num', 'month')
            ->orderBy('month_num')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Collections',
                    'data' => $payments->pluck('total')->toArray(),
                    'borderColor' => '#10B981',
                    'fill' => false,
                ],
            ],
            'labels' => $payments->pluck('month')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    public static function canView(): bool
    {
        return auth()->user()->hasRole(['accountant', 'bursar', 'financial_manager']);
    }
}
