<?php

namespace App\Livewire;

use Livewire\Component;
use Filament\Pages\Page;
use Filament\Tables\Table;
use App\Models\SubsPayment;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;
use App\Filament\Sms\Pages\SubscriptionReceipt;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Facades\Log;

class Billing extends Page implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static string $view = 'livewire.billing';

    public $subscriptionCode = '';
    public $school;
    public $planName;
    public $nextBillingDate;
    public $subscription;

    public function mount()
    {

        $user = Auth::user();

        $this->school = $user->schools->first();

        $this->subscription = $this->school->subscriptions->where('status', 'active')->first();

        $this->planName = $this->subscription->plan->name ?? null;

        $this->nextBillingDate = $this->subscription->next_payment_date ?? null;

        $this->subscriptionCode = $this->subscription->subscription_code ?? null;
    }



    public function table(Table $table): Table
    {
        return $table
            ->query(SubsPayment::query())
            ->columns([
                TextColumn::make('subscription.plan.name')->label('Subscription Plan'),
                TextColumn::make('amount')->money('NGN', true),
                TextColumn::make('subscription.status')->label('Subscription Status')->badge()
                    ->color(fn (string $state): string => match ($state) {

                        'active' => 'success',
                        'expired' => 'danger',
                        'cancelled' => 'warning',
                    }),
                TextColumn::make('status')->label('Payment Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'paid' => 'success',
                    }),
                TextColumn::make('payment_date')->dateTime('F j, Y g:i A'),
                TextColumn::make('subscriptions.next_payment_date')->hidden(!$this->subscriptionCode)->dateTime('F j, Y g:i A'),
            ])
            ->filters([
                // ...
            ])
            ->actions([
                Action::make('View Receipt')
                    ->url(function (SubsPayment $record): string {
                        $user = Auth::user(); // Get the authenticated user
                        $school = $user->schools->first(); // Get the first school associated with the user
                        $subscription = $school->subscriptions->where('status', 'active')->first(); // Get the active subscription

                        return SubscriptionReceipt::getUrl(['tenant' => $school->slug, 'record' => $record->id]);
                    })
                    ->visible(fn (SubsPayment $record): bool => $record->status === 'paid')
                    ->openUrlInNewTab()
            ])
            ->bulkActions([
                // ...
            ]);
    }

    public function manageSubscription()
    {

        if (empty($this->subscriptionCode) && !$this->subscription) {
            Notification::make()
                ->title('No Subscription Available.')
                ->danger()
                ->send();
            return;
        } else if ($this->subscription && empty($this->subscriptionCode) && $this->subscription->is_on_trial) {
            Notification::make()
                ->title('You are on Free Trial.')
                ->info()
                ->send();
            return;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
        ])->get("https://api.paystack.co/subscription/{$this->subscriptionCode}/manage/link");

        if ($response->successful()) {
            $link = $response->json()['data']['link'] ?? null;
            if ($link) {
                return redirect()->to($link);
            }
            Notification::make()
                ->title('Link Not Found.')
                ->warning()
                ->send();
        } else {
            // Handle errors here
            Notification::make()
                ->title('Failed to Fetch Subscription Link.')
                ->danger()
                ->send();
        }
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

    public function cancelSubscription()
    {
        $this->subscription = $this->school->subscriptions->where('status', 'active')->first();

        if (!$this->subscription) {
            Notification::make()
                ->title('No Active Subscription Available.')
                ->danger()
                ->send();
            return;
        }
        $this->dispatch('close-modal', id: 'cancel-subscription-modal');
        // Check if the subscription has a Paystack subscription code
        if (empty($this->subscription->subscription_code)) {
            // Logic to cancel the subscription within the app
            $this->subscription->status = 'cancelled'; // Example field to indicate active status
            $this->subscription->cancelled_at = now(); // Example field to store cancellation time
            $this->subscription->save();
            $this->dispatch('close-modal', id: 'cancel-subscription-modal');

            $this->refreshComponent();
            
            Notification::make()
                ->title('Subscription Cancelled Successfully.')
                ->success()
                ->send();
        } else {
            // Use existing logic to cancel through Paystack
            $this->subscriptionCode = $this->subscription->subscription_code;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
            ])->post("https://api.paystack.co/subscription/disable", [
                'code' => $this->subscriptionCode,
                'token' => $this->subscription->token,
            ]);

            Log::info($response->json());

            if ($response->successful()) {

                $this->dispatch('close-modal', id: 'cancel-subscription-modal');

                $this->refreshComponent();

                Notification::make()
                    ->title('Subscription Cancelled Successfully.')
                    ->success()
                    ->send();
            } else {
                // Handle errors here
                $this->dispatch('close-modal', id: 'cancel-subscription-modal');

                $this->refreshComponent();
                Notification::make()
                    ->title('Failed to Cancel Subscription through Paystack.')
                    ->danger()
                    ->send();
            }
        }
    }
}
