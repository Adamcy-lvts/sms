<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfUserNotSubscribed
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            // Redirect to login if the user is not authenticated
            return redirect()->route('login');
        }
    
        $user = Auth::user();
        $school = $user->schools->first();
    
        // Bypass for pricing page with matching tenant slug
        if ($request->route()->getName() === 'filament.sms.pages.pricing-page' && $request->route('tenant') === $school->slug) {
            return $next($request);
        }
    
        // Bypass for billing page
        if ($request->routeIs('filament.sms.tenant.billing')) {
            return $next($request);
        }
    
        // Redirect to billing if no school or no active subscription
        // if (!$school || !$school->subscriptions()->where('status', 'active')->exists()) {
        //     return redirect()->route('filament.sms.tenant.billing', ['tenant' => $school->slug]);
        // }
    
        // Proceed with the request if none of the conditions above are met
        return $next($request);
    }
}
