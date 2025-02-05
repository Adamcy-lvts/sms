<?php

namespace App\Filament\Sms\Widgets;

use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Facades\Filament;

class AccountantStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected function getStats(): array
    {
        $today = now()->toDateString();
        $thisMonth = now()->format('Y-m');
        $tenant = Filament::getTenant();

        $todayPayments = Payment::where('school_id', $tenant->id)
            ->whereHas('status', fn($q) => $q->whereIn('name', ['paid', 'partial']))
            ->whereDate('paid_at', $today)
            ->sum('deposit');

        $monthlyPayments = Payment::where('school_id', $tenant->id)
            ->whereHas('status', fn($q) => $q->whereIn('name', ['paid', 'partial']))
            ->whereYear('paid_at', now()->year)
            ->whereMonth('paid_at', now()->month)
            ->sum('deposit');

        $pendingPayments = Payment::where('school_id', $tenant->id)
            ->whereHas('status', fn($q) => $q->where('name', 'partial'))
            ->count();

        $balanceTotal = Payment::where('school_id', $tenant->id)
            ->whereHas('status', fn($q) => $q->where('name', 'partial'))
            ->where('balance', '>', 0)
            ->sum('balance');

        return [
            Stat::make("Today's Collections", formatNaira($todayPayments))
                ->description('Total payments received today')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Monthly Collections', formatNaira($monthlyPayments))
                ->description('Total for ' . now()->format('F Y'))
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('Partial Payments', $pendingPayments)
                ->description('Payments pending completion')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Pending Balances', formatNaira($balanceTotal))
                ->description('Total outstanding balances')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('danger'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()->hasRole(['accountant', 'bursar', 'financial_manager']);
    }
}
