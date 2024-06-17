<?php

namespace App\Livewire;

use App\Models\SubsPayment;
use Livewire\Component;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;


class Billing extends Page implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static string $view = 'livewire.billing';

    public $subscriptionCode = '';
    public $school;
    public $planName;
    public $nextBillingDate;

    public function mount()
    {

        $user = Auth::user();

        $this->school = $user->schools->first();

        $subscription = $this->school->subscriptions->where('status', 'active')->first();

        $this->planName = $subscription->plan->name ?? null;

        $this->nextBillingDate = $subscription->next_payment_date ?? null;

        $this->subscriptionCode = $subscription->subscription_code ?? null;
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
                TextColumn::make('subscriptions.next_payment_date')->dateTime('F j, Y g:i A'),
            ])
            ->filters([
                // ...
            ])
            ->actions([
                // ...
            ])
            ->bulkActions([
                // ...
            ]);
    }

    public function manageSubscription()
    {

        if (empty($this->subscriptionCode)) {
            Notification::make()
                ->title('No Subscription Available.')
                ->danger()
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


    public function cancelSubscription()
    {
        $subscription = $this->school->subscriptions->where('status', 'active')->first();

        if (!$subscription) {
            Notification::make()
                ->title('No Active Subscription Available.')
                ->danger()
                ->send();
            return;
        }

        // Check if the subscription has a Paystack subscription code
        if (empty($subscription->subscription_code)) {
            // Logic to cancel the subscription within the app
            $subscription->status = 'cancelled'; // Example field to indicate active status
            $subscription->cancelled_at = now(); // Example field to store cancellation time
            $subscription->save();

            Notification::make()
                ->title('Subscription Cancelled Successfully.')
                ->success()
                ->send();
        } else {
            // Use existing logic to cancel through Paystack
            $this->subscriptionCode = $subscription->subscription_code;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
            ])->post("https://api.paystack.co/subscription/disable", [
                'code' => $this->subscriptionCode,
            ]);

            if ($response->successful()) {
                Notification::make()
                    ->title('Subscription Cancelled Successfully.')
                    ->success()
                    ->send();
            } else {
                // Handle errors here
                Notification::make()
                    ->title('Failed to Cancel Subscription through Paystack.')
                    ->danger()
                    ->send();
            }
        }
    }
}
