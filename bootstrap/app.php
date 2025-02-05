<?php

use Psr\Log\LogLevel;
use Illuminate\Foundation\Application;
use App\Http\Middleware\SchoolPermission;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\RedirectIfUserNotSubscribed;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'webhook/paystack', // <-- exclude this route
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(function (Throwable $e) {
            try {
                $user = optional(auth()->user())->only('id', 'first_name','last_name', 'email');
                $school = optional(auth()->user())->schools()->first()?->only('id', 'name') ?? null;

                Notification::route('mail', config('app.admin_email'))
                    ->notify(new \App\Notifications\ExceptionOccurred(
                        message: $e->getMessage(),
                        file: $e->getFile(),
                        line: $e->getLine(),
                        trace: $e->getTraceAsString(),
                        url: request()->fullUrl(),
                        method: request()->method(),
                        ip: request()->ip(),
                        user: $user,
                        school: $school
                    ));
            } catch (Throwable $mailException) {
                logger()->error('Email sending failed: ' . $mailException->getMessage());
            }
        });
    })
    ->create();
