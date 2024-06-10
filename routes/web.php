<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';


// Laravel 8 & 9
Route::post('/pay', [App\Http\Controllers\ProcessPaymentController::class, 'redirectToGateway'])->name('pay');

// Laravel 8 & 9
Route::get('/payment/callback', [App\Http\Controllers\ProcessPaymentController::class, 'handleGatewayCallback']);

Route::webhooks('webhook/paystack', 'paystack');