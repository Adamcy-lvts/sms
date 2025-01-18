<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Filament\Pages\Page;
use Filament\Tables\Table;
use App\Models\SubsPayment;
use App\Models\Subscription;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;
use App\Filament\Sms\Pages\SubscriptionReceipt;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

class Billing extends Page implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static string $view = 'livewire.billing';

    public ?string $subscriptionCode = '';
    public $school;
    public ?string $planName = null;
    public ?string $nextBillingDate = null;
    public $subscription;

    protected const PAYSTACK_API_URL = 'https://api.paystack.co';

    public function mount(): void
    {
        $user = Auth::user();
        $this->school = $user->schools->first();
        $this->loadSubscriptionData();
    }

    protected function loadSubscriptionData(): void
    {
        $this->subscription = $this->school->subscriptions()
            ->where('status', 'active')
            ->first();

        if ($this->subscription) {
            $this->planName = $this->subscription->plan?->name;
            $this->nextBillingDate = Carbon::parse($this->subscription->next_payment_date)->format('d F Y');
            $this->subscriptionCode = $this->subscription->subscription_code;
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Subscription::query()
                    ->with(['plan', 'payments']) // Eager load relationships
                    ->where('school_id', $this->school->id)
            )
            ->columns([
                TextColumn::make('plan.name')
                    ->label('Plan')
                    ->sortable(),
                TextColumn::make('plan.price')
                    ->label('Amount')
                    ->formatStateUsing(fn($record) => $record->is_on_trial ? 
                        formatNaira(0) : 
                        formatNaira($record->plan->price)
                    )
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'expired' => 'danger',
                        'cancelled' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($record) => $record->is_on_trial ? 'Trial' : $record->status)
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label('Start Date')
                    ->dateTime('F j, Y')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('End Date')
                    ->dateTime('F j, Y')
                    ->sortable(),
                TextColumn::make('next_payment_date')
                    ->label('Next Payment')
                    ->dateTime('F j, Y')
                    ->sortable()
                    ->visible(fn($record) => $record?->status === 'active'), // Added null-safe operator
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('View Receipt')
                    ->url(
                        fn(Subscription $record): string =>
                        SubscriptionReceipt::getUrl([
                            'tenant' => $this->school->slug,
                            'record' => $record->payments->latest()->first()?->id
                        ])
                    )
                    ->visible(
                        fn(Subscription $record): bool =>
                        $record->payments()->where('status', 'paid')->exists()
                    )
                    ->openUrlInNewTab()
            ]);
    }

    public function manageSubscription(): void
    {
        if ($this->cannotManageSubscription()) {
            return;
        }

        try {
            $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
                ->get(self::PAYSTACK_API_URL . "/subscription/{$this->subscriptionCode}/manage/link");

            if ($response->successful()) {
                $link = $response->json()['data']['link'] ?? null;
                if ($link) {
                    redirect()->to($link);
                    return;
                }
            }

            throw new \Exception('Failed to fetch management link');
        } catch (\Exception $e) {
            Log::error('Subscription management error: ' . $e->getMessage());
            $this->notifyError('Failed to fetch subscription management link.');
        }
    }

    protected function cannotManageSubscription(): bool
    {
        if (empty($this->subscriptionCode) && !$this->subscription) {
            $this->notifyError('No subscription available.');
            return true;
        }

        if ($this->subscription && empty($this->subscriptionCode) && $this->subscription->is_on_trial) {
            $this->notifyInfo('You are currently on a free trial.');
            return true;
        }

        return false;
    }

    protected function notifyError(string $message): void
    {
        Notification::make()
            ->title($message)
            ->danger()
            ->send();
    }

    protected function notifyInfo(string $message): void
    {
        Notification::make()
            ->title($message)
            ->info()
            ->send();
    }

    public function subscribe()
    {
        return redirect()->route('filament.sms.pages.pricing-page', ['tenant' => $this->school->slug]);
    }

    protected function refreshComponent()
    {
        $this->dispatch('$refresh');
    }


    public function cancelSubs()
    {
        $this->dispatch('open-modal', id: 'cancel-subscription-modal');
    }

    public function cancelSubscription(): void
    {
        try {
            $this->subscription = $this->school->subscriptions()
                ->where('status', 'active')
                ->firstOrFail();

            if (empty($this->subscription->subscription_code)) {
                $this->handleLocalCancellation();
            } else {
                $this->handlePaystackCancellation();
            }
        } catch (\Exception $e) {
            Log::error('Subscription cancellation error: ' . $e->getMessage());
            $this->notifyError('Failed to cancel subscription.');
        } finally {
            $this->dispatch('close-modal', id: 'cancel-subscription-modal');
            $this->refreshComponent();
        }
    }

    protected function handleLocalCancellation(): void
    {
        $this->subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        Notification::make()
            ->title('Subscription cancelled successfully.')
            ->success()
            ->send();
    }

    protected function handlePaystackCancellation(): void
    {
        $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
            ->post(self::PAYSTACK_API_URL . '/subscription/disable', [
                'code' => $this->subscription->subscription_code,
                'token' => $this->subscription->token,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to cancel subscription through Paystack');
        }

        Notification::make()
            ->title('Subscription cancelled successfully.')
            ->success()
            ->send();
    }
}
