<?php

namespace App\Filament\Sms\Widgets;

use App\Models\Status;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\PaymentType;
use Filament\Support\RawJs;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class RevenueByTypeChart extends ChartWidget
{
    use HasWidgetShield;
    protected static ?string $heading = 'Revenue Distribution';
    protected static ?string $description = 'Revenue breakdown by payment type';
    protected static ?int $sort = 2;
    // protected int | string | array $columnSpan = 'full';
    protected static ?string $maxHeight = '280px';

    public function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $tenant = Filament::getTenant();
        $currentSession = config('app.current_session');
        $currentTerm = config('app.current_term');

        if (!$tenant || !$currentSession || !$currentTerm) {
            return $this->getEmptyDataset();
        }

        $paidStatusId = Status::where('type', 'payment')
            ->where('name', 'paid')
            ->value('id');

        if (!$paidStatusId) {
            return $this->getEmptyDataset();
        }

        $data = PaymentItem::query()
            ->join('payment_types', 'payment_items.payment_type_id', '=', 'payment_types.id')
            ->join('payments', 'payment_items.payment_id', '=', 'payments.id')
            ->where('payments.school_id', $tenant->id)
            ->where('payments.academic_session_id', $currentSession->id)
            ->where('payments.term_id', $currentTerm->id)
            ->where('payments.status_id', $paidStatusId)
            ->select('payment_types.name')
            ->selectRaw('COALESCE(SUM(payment_items.deposit), 0) as total')
            ->groupBy('payment_types.id', 'payment_types.name')
            ->orderByDesc('total')
            ->get();

        $colors = [
            '#10B981', '#3B82F6', '#F59E0B', '#8B5CF6',
            '#EC4899', '#14B8A6', '#F97316',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $data->pluck('total')->map(fn($value) => $value ?? 0)->toArray(),
                    'backgroundColor' => array_slice($colors, 0, max(1, $data->count())),
                ],
            ],
            'labels' => $data->pluck('name')->map(fn($name) => $name ?? 'Unknown')->toArray(),
        ];
    }

    protected function getEmptyDataset(): array
    {
        return [
            'datasets' => [['label' => 'Revenue', 'data' => [], 'backgroundColor' => []]],
            'labels' => [],
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
        {
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || 'Unknown';
                            let value = parseFloat(context.raw || 0);
                            let total = (context.dataset.data || []).reduce((a, b) => parseFloat(a || 0) + parseFloat(b || 0), 0);
                            let percentage = total > 0 ? ((value * 100) / total).toFixed(1) : '0.0';
                            return label + ': ₦' + value.toLocaleString() + ' (' + percentage + '%)';
                        }
                    }
                }
            },
            cutout: '60%',
        }
        JS);
    }
}
