<?php

namespace App\Filament\Agent\Widgets;

use Illuminate\Support\Facades\Auth;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class AgentStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $agent = Auth::user()->agent;

        // Total referred schools
        $totalReferredUsers = $agent->schools()->count();

        // Total subscriptions
        // Directly using sum on the query to avoid loading models into memory
        $totalSubscriptions = $agent->schools()->withCount('subscriptions')->sum('subscriptions_count');


        // Total commission earned from referral payments
        // This assumes the `amount` in AgentPayment is the commission amount to be summed up
        $totalCommission = $agent->agentPayments()->sum('amount');

        return [
            Stat::make('Total Referred Users', $totalReferredUsers)
                ->description('Number of schools referred by you')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),
            Stat::make('Total Subscriptions', $totalSubscriptions)
                ->description('Total number of active subscriptions')
                ->descriptionIcon('heroicon-o-rectangle-stack')
                ->color('success'),
            Stat::make('Total Commission Earned', number_format($totalCommission, 2) . ' NGN')
                ->description('Total commission earned from referrals')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('info'),
        ];
    }
}
