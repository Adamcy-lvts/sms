<?php

namespace App\Filament\Sms\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Filament\Facades\Filament;

class PaymentMethodsChart extends ChartWidget
{
    protected static ?int $sort = 4;
    protected static ?string $heading = 'Payment Methods';
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $data = Payment::where('payments.school_id', Filament::getTenant()->id)
            ->whereNotNull('payments.paid_at')
            ->whereMonth('payments.paid_at', now()->month)
            ->join('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
            ->groupBy('payment_methods.name')
            ->selectRaw('payment_methods.name, SUM(payments.deposit) as total')
            ->get();

        return [
            'datasets' => [
                [
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => ['#10B981', '#3B82F6', '#F59E0B'],
                ],
            ],
            'labels' => $data->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    public static function canView(): bool
    {
        return auth()->user()->hasRole(['accountant', 'bursar', 'financial_manager']);
    }
}
