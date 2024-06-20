<?php

namespace App\Filament\Sms\Pages;

use Filament\Pages\Page;
use App\Models\SubsPayment;
use Illuminate\Http\Request;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\Browsershot\Browsershot;
use Filament\Notifications\Notification;

class SubscriptionReceipt extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.sms.pages.subscription-receipt';

    protected static bool $shouldRegisterNavigation = false;

    public $payment;
    public $receipt;
    public $record;

    public function mount(Request $request): void
    {
        // dd($record);
        $this->record = $request->query('record');

        $this->payment = SubsPayment::find($this->record);

        $this->receipt = $this->payment->SubscriptionReceipt;

        // dd($this->receipt->user);
    }

    public function downloadReceipt()
    {

        $pdfName = $this->payment->school->name . '_' .now(). '_receipt.pdf';
        $receiptPath = storage_path("app/{$pdfName}");


        Pdf::view('pdfs.subscription_receipt_pdf', [
            'payment' => $this->payment,
            'receipt' => $this->receipt

        ])->withBrowsershot(function (Browsershot $browsershot) {
            $browsershot->setChromePath(config('app.chrome_path'));
        })->save($receiptPath);

        Notification::make()
            ->title('Receipt downloaded successfully.')
            ->success()
            ->send();

        return response()->download($receiptPath, $pdfName, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }
}
