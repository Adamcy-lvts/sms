<?php

namespace App\Notifications;

use App\Models\Admission;
use Illuminate\Bus\Queueable;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AdmissionApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Admission $admission, protected ?string $pdfPath = null)
    {
        $this->pdfPath = $pdfPath;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Admission Approved - {$this->admission->school->name}")
            ->markdown('emails.admission-approved', [
                'admission' => $this->admission,
                'url' => route('filament.sms.resources.admissions.view-letter', [
                    'tenant' => $this->admission->school->slug,
                    'record' => $this->admission->id,
                ])
            ]);

        if ($this->pdfPath && Storage::exists($this->pdfPath)) {
            $message->attach(Storage::path($this->pdfPath), [
                'as' => "admission-letter-{$this->admission->admission_number}.pdf",
                'mime' => 'application/pdf',
            ]);
        }

        return $message;
    }
}
