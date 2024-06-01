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
            return redirect()->route('login');
        }

        $user = Auth::user();
        $school = $user->schools->first();

        if ($request->routeIs('filament.sms.tenant.billing')) {
            return $next($request);  // Continue if already on the billing page
        }

        // if (!$school || !$school->subscriptions()->where('status', 'active')->exists()) {
        //     return redirect()->route('filament.sms.tenant.billing', ['tenant' => $school->slug]);
        // }

        return $next($request);
    }
}
