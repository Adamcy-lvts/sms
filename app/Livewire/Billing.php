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

    public function mount()
    {

        $user = Auth::user();

        $school = $user->schools->first();

        $subscription = $school->subscriptions->first();


        $this->subscriptionCode = $subscription->subscription_code ?? null;
    }



    public function table(Table $table): Table
    {
        return $table
            ->query(SubsPayment::query())
            ->columns([
                TextColumn::make('amount'),
                TextColumn::make('status'),
                TextColumn::make('payment_date')->dateTime(),
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
}
