<?php

namespace App\Filament\Sms\Billing;

use Closure;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Filament\Billing\Providers\Contracts\Provider;
use App\Http\Middleware\RedirectIfUserNotSubscribed;
use App\Livewire\Billing;

class BillingProvider implements Provider
{
    /**
     * Returns a Closure that when invoked performs a redirect.
     *
     * @return String
     */
    public function getRouteAction(): String
    {

        // return function (): RedirectResponse {
        //     $user = Auth::user();
        //     $school = $user->schools->first();
            return Billing::class;
        // };
    }


    public function getSubscribedMiddleware(): string
    {
        return RedirectIfUserNotSubscribed::class;
    }
}
