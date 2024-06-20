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

        $user = Auth::user();
        $school = $user->schools->first();
        
        // Assuming $request->route() can be used to get the current route name
        // and $request->route('tenant') to get the route parameter 'tenant'
        if ($request->route()->getName() === 'filament.sms.pages.pricing-page' && $request->route('tenant') === $school->slug) {
            // Bypass the subscription check for this specific route and parameter
            return $next($request);
        }

        // Your existing subscription check logic here
        // Redirect if the user is not subscribed
        // ...

        return $next($request);


        if (!Auth::check()) {
            return redirect()->route('login');
        }

      

        if ($request->routeIs('filament.sms.tenant.billing')) {
            return $next($request);  // Continue if already on the billing page
        }

        if (!$school || !$school->subscriptions()->where('status', 'active')->exists()) {
            return redirect()->route('filament.sms.tenant.billing', ['tenant' => $school->slug]);
            // return redirect()->route('filament.sms.pages.pricing-page', ['tenant' => $school->slug]);
        }

        return $next($request);
    }
}
