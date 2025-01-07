<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Filament\Notifications\Notification;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->status) {
            if ($user->status->name !== 'active') {
                // Log the user out
                auth()->logout();
                
                // Clear session
                session()->invalidate();
                session()->regenerateToken();

                // Send notification
                Notification::make()
                    ->title('Access Denied')
                    ->body("Your account is {$user->status->name}. Please contact support.")
                    ->danger()
                    ->persistent()
                    ->send();

                // Redirect to login
                return redirect()->route('filament.sms.auth.login');
            }
        }

        return $next($request);
    }
}
