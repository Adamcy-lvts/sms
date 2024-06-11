<?php

namespace App\Livewire;

use Livewire\Component;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Filament\Notifications\Notification;

class Billing extends Page
{
    protected static string $view = 'livewire.billing';

    public $subscriptionCode = '';

    public function mount() {
         
        $user = Auth::user();

        $school = $user->schools->first();

        $subscription = $school->subscriptions->first();

        // dd($subscription);

        $this->subscriptionCode = $subscription->subscription_code ?? null;
        // dd($this->subscriptionCode);
        
    }

    public function manageSubscription()
    {
        // dd($this->subscriptionCode);
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
