<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\AdmissionLetterController;

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

Route::view('/pdf-view', 'pdfs.subscription_receipt_pdf')->name('pdf-view');


Route::get('/receipt/{payment}/{receipt}', [ReceiptController::class, 'show'])->name('receipt.show');

Route::get('/admission-letter/{admission}', [AdmissionLetterController::class, 'show'])->name('admission-letter.show');

Route::get('/admission-letter-pdf/{admission}', [AdmissionLetterController::class, 'downloadAdmissionLetter'])->name('download.admission-letter.pdf');