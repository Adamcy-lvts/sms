<?php

namespace App\Filament\Sms\Resources\PaymentResource\Pages;

use Filament\Actions;
use Illuminate\Support\Str;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Filament\Sms\Resources\PaymentResource;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;
    protected static string $view = 'filament.sms.resources.payment-resource.pages.view-payment';

    public string $QrViewUrl = '';
    public $qrLogo;

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $school = Filament::getTenant();


        $this->qrLogo = Storage::disk('public')->path($this->record->school->logo);

        $this->QrViewUrl = PaymentResource::getUrl('view', [
            'record' => $this->record->id,
            'tenant' => $school->id,
        ]);
    }

    protected function getHeaderActions(): array
    {
        $school = Filament::getTenant();
        return [
            Action::make('downloadReceipt')
                ->label('Download Receipt')
                ->icon('heroicon-o-arrow-down-tray')
                ->action('downloadReceipt')
                ->color('success'),

            Action::make('printReceipt')
                ->label('Print Receipt')
                // ->url(fn () => route('student.receipt.print', ['payment'=>$this->record,'tenant'=> $school->id ]))
                ->url(fn() => route('student.receipt.print', [
                    'tenant' => $school->id,
                    'payment' => $this->record->id
                ]))
                ->icon('heroicon-o-printer')
                ->openUrlInNewTab()
                ->color('warning')
                // ->url(fn () => route('student.receipt.print', ['record'=>$this->record,'tenant'=> $ school->id ]))

                ->icon('heroicon-o-printer')
                ->openUrlInNewTab()
                ->color('warning'),

            Actions\EditAction::make()
                ->icon('heroicon-o-pencil-square'),
        ];
    }

    public function downloadReceipt()
    {
        try {
            
            $school = $this->record->school;
            $fileName = Str::slug("{$school->slug}-receipt-{$this->record->reference}") . '.pdf';
            $directory = storage_path("app/public/{$school->slug}/documents");

            // Fix logo checking and loading
            $logoData = null;
            if ($school->logo) {
                // Remove the 'public/' prefix if it exists in the stored path
                $logoPath = str_replace('public/', '', $school->logo);

                if (Storage::disk('public')->exists($logoPath)) {
                    $fullLogoPath = Storage::disk('public')->path($logoPath);
                    $extension = pathinfo($fullLogoPath, PATHINFO_EXTENSION);
                    $logoData = 'data:image/' . $extension . ';base64,' . base64_encode(
                        Storage::disk('public')->get($logoPath)
                    );
                }
            }

            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $receiptPath = "{$directory}/{$fileName}";

            // Generate PDF with logo data
            Pdf::view('pdfs.student-payment-receipt', [
                'payment' => $this->record->load([
                    'student.classRoom',
                    'academicSession',
                    'term',
                    'status',
                    'paymentType',
                    'paymentMethod'
                ]),
                'school' => $school,
                'QrViewUrl' => $this->QrViewUrl,
                'qrLogo' => $this->qrLogo,
                'logoData' => $logoData,
                'isPrintMode' => false
            ])
                ->format('a4')
                ->withBrowsershot(function (Browsershot $browsershot) {
                    $browsershot->setChromePath(config('app.chrome_path'))
                        // ->margins(10, 10, 10, 10)
                        ->format('A4')
                        ->showBackground()
                        ->waitUntilNetworkIdle();
                })
                ->save($receiptPath);

            // Check if file was created successfully
            if (!file_exists($receiptPath)) {
                throw new \Exception('Failed to generate PDF file');
            }

            Notification::make()
                ->title('Receipt downloaded successfully.')
                ->success()
                ->send();

            return response()->download($receiptPath, $fileName, [
                'Content-Type' => 'application/pdf',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            // Log the error
            Log::error('PDF Generation Failed', [
                'error' => $e->getMessage(),
                'payment_id' => $this->record->id,
                'school' => $school->slug ?? null
            ]);

            Notification::make()
                ->title('Error generating receipt')
                ->body('Something went wrong while generating the receipt.')
                ->danger()
                ->send();

            return null;
        }
    }

    protected function getViewData(): array
    {
        $this->qrCode = QrCode::format('svg')
            ->size(72)
            ->errorCorrection('H')
            ->margin(1)
            ->color(0, 0, 0)
            ->merge($this->qrLogo, 0.3, true)
            ->generate($this->QrViewUrl);
        return [
            'qrUrl' => $this->QrViewUrl,
            'qrCode' => $this->qrCode,
        ];
    }
}
