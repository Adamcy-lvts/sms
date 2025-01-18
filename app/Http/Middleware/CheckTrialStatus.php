<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Filament\Facades\Filament;
use Symfony\Component\HttpFoundation\Response;

class CheckTrialStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $school = Filament::getTenant();
        $subscription = $school->currentSubscription();

        if ($subscription && $subscription->trialHasExpired()) {
            // Redirect to subscription page or show trial expired notice
            return redirect()->route('filament.sms.pages.pricing-page', [
                'tenant' => $school->slug
            ])->with('warning', 'Your trial period has expired');
        }

        return $next($request);
    }
}
