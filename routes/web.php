<?php

use App\Livewire\ReportProgress;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\AdmissionLetterController;
use App\Http\Controllers\Sms\PaymentReceiptController;
use App\Http\Controllers\ReportTemplatePreviewController;




Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__ . '/auth.php';


// Laravel 8 & 9
Route::post('/pay', [App\Http\Controllers\ProcessPaymentController::class, 'redirectToGateway'])->name('pay');

// Laravel 8 & 9
Route::get('/payment/callback', [App\Http\Controllers\ProcessPaymentController::class, 'handleGatewayCallback']);

Route::webhooks('webhook/paystack', 'paystack');

Route::view('/pdf-view', 'pdfs.subscription_receipt_pdf')->name('pdf-view');


Route::get('/receipt/{payment}/{receipt}', [ReceiptController::class, 'show'])->name('receipt.show');

Route::get('/admission-letter/{admission}', [AdmissionLetterController::class, 'show'])->name('admission-letter.show');

Route::get('/admission-letter-pdf/{admission}', [AdmissionLetterController::class, 'downloadAdmissionLetter'])->name('download.admission-letter.pdf');

// Route::middleware(['auth'])->group(function () {
//     Route::prefix('sms/{tenant}')->group(function () {
//         // Payment Receipt Routes


//         // Route::get('payments/{payment}/receipt/download', [PaymentReceiptController::class, 'download'])
//         //     ->name('student.receipt.download');
//     });
// });

Route::get('payments/{payment}/{tenant}/receipt/print', [PaymentReceiptController::class, 'print'])
    ->name('student.receipt.print');


// web.php
Route::get('report-cards/{student}/preview', [ReportCardController::class, 'preview'])
    ->name('report-cards.preview');

// Route::get('/report-progress/{batchId}', function (string $batchId) {
//     return app(App\Services\BulkReportCardService::class)->getProgress($batchId);
// })->name('report.progress');

Route::get('/report-progress/{batchId}', ReportProgress::class)->name('report.progress');

Route::get('/reports/download/{file}', function ($file) {
    $path = storage_path("app/public/reports/bulk/{$file}");

    if (!File::exists($path)) {
        return back()->with('error', 'File not found');
    }

    // Schedule cleanup after 1 hour
    dispatch(function () use ($path) {
        File::delete($path);
    })->delay(now()->addHour());

    return response()->download($path);
})->name('reports.download');
