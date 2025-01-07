<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Term;
use App\Models\Student;
use App\Models\Payment;
use Filament\Facades\Filament;
use App\Services\StatusService;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class StudentPaymentStats extends BaseWidget
{
    public ?Student $student = null;
    protected StatusService $statusService;

    public function boot(StatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    public function mount(?Student $student = null)
    {
        $this->student = $student ?? Filament::getTenant();
    }

    protected function getStats(): array
    {
        return [
            $this->getTotalPaymentsStat(),
            $this->getOutstandingBalanceStat(),
            $this->getPaymentComplianceStat(),
            $this->getLatestPaymentStat(),
        ];
    }

    protected function getTotalPaymentsStat(): Stat
    {
        $paidStatusId = $this->statusService->getPaymentStatusId('paid');
        
        $totalPaid = Payment::where('student_id', $this->student->id)
            ->where('status_id', $paidStatusId)
            ->sum('deposit');

        return Stat::make('Total Payments Made', formatNaira($totalPaid))
            ->description('Total amount paid to date')
            ->descriptionIcon('heroicon-m-banknotes')
            ->color('success');
    }

    protected function getOutstandingBalanceStat(): Stat
    {
        $pendingStatusId = $this->statusService->getPaymentStatusId('pending');
        $partialStatusId = $this->statusService->getPaymentStatusId('partial');
        $overdueStatusId = $this->statusService->getPaymentStatusId('overdue');

        $totalBalance = Payment::where('student_id', $this->student->id)
            ->whereIn('status_id', [$pendingStatusId, $partialStatusId, $overdueStatusId])
            ->sum('balance');

        $color = match(true) {
            $totalBalance <= 0 => 'success',
            $totalBalance <= 1000 => 'warning',
            default => 'danger'
        };

        $description = $totalBalance <= 0 
            ? 'No outstanding balance' 
            : 'Outstanding balance needs attention';

        return Stat::make('Outstanding Balance', formatNaira($totalBalance))
            ->description($description)
            ->descriptionIcon('heroicon-m-exclamation-circle')
            ->color($color);
    }

    protected function getPaymentComplianceStat(): Stat
    {
        $currentTerm = Term::find(config('app.current_term')->id);
        $paidStatusId = $this->statusService->getPaymentStatusId('paid');
        $partialStatusId = $this->statusService->getPaymentStatusId('partial');
        
        $totalPayments = Payment::where('student_id', $this->student->id)
            ->where('term_id', $currentTerm->id)
            ->whereIn('status_id', [$paidStatusId, $partialStatusId])
            ->count();

        $onTimePayments = Payment::where('student_id', $this->student->id)
            ->where('term_id', $currentTerm->id)
            ->where('status_id', $paidStatusId)
            ->where('paid_at', '<=', DB::raw('due_date'))
            ->count();

        $complianceRate = $totalPayments > 0 
            ? ($onTimePayments / $totalPayments) * 100 
            : 0;

        $color = match(true) {
            $complianceRate >= 90 => 'success',
            $complianceRate >= 70 => 'warning',
            default => 'danger'
        };

        return Stat::make('Payment Compliance', number_format($complianceRate, 1) . '%')
            ->description('On-time payment rate')
            ->descriptionIcon('heroicon-m-clock')
            ->color($color)
            ->chart([$complianceRate]);
    }

    protected function getLatestPaymentStat(): Stat
    {
        $paidStatusId = $this->statusService->getPaymentStatusId('paid');
        $partialStatusId = $this->statusService->getPaymentStatusId('partial');

        $latestPayment = Payment::where('student_id', $this->student->id)
            ->whereIn('status_id', [$paidStatusId, $partialStatusId])
            ->latest('paid_at')
            ->first();

        if (!$latestPayment) {
            return Stat::make('Latest Payment', 'No payments')
                ->description('No payment history found')
                ->color('gray');
        }

        $daysSincePayment = Carbon::parse($latestPayment->paid_at)->diffInDays(now());
        
        $color = match(true) {
            $daysSincePayment <= 30 => 'success',
            $daysSincePayment <= 60 => 'warning',
            default => 'danger'
        };

        $description = $daysSincePayment === 0 
            ? 'Paid today'
            : abs(ceil($daysSincePayment)) . ' days ago';

        return Stat::make('Latest Payment', formatNaira($latestPayment->deposit))
            ->description($description)
            ->descriptionIcon('heroicon-m-calendar')
            ->color($color);
    }
}
