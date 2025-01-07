<?php

namespace App\Mail;

use App\Models\Admission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Storage;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Contracts\Queue\ShouldQueue;

class ApplicationApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Admission $admission,
        protected string $pdfPath,
        protected string $pdfName
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            // Professional subject line with school name
            subject: "Admission Approved - {$this->admission->school->name}",
            // Optional: Add school email as sender
            from: $this->admission->school->email,
            // Set email priority
            tags: ['admission', 'approval'],
            metadata: [
                'admission_id' => $this->admission->id,
                'school_id' => $this->admission->school_id,
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.admission-approved',
            with: [
                'admission' => $this->admission,
                'school' => $this->admission->school,
                'url' => route('filament.sms.resources.admissions.view-letter', [
                    'tenant' => $this->admission->school->slug,
                    'record' => $this->admission->id,
                ]),
                // Add extra context data
                'admissionDate' => $this->admission->admitted_date?->format('F j, Y'),
                'classInfo' => $this->admission->classRoom?->name ?? 'Not Assigned',
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        try {
            $fullPath = Storage::path($this->pdfPath);

            if (file_exists($fullPath) && is_readable($fullPath)) {
                return [
                    Attachment::fromPath($fullPath)
                        ->as($this->pdfName)
                        ->withMime('application/pdf'),
                ];
            }

            Log::warning('Admission letter PDF not accessible', [
                'path' => $this->pdfPath,
                'exists' => file_exists($fullPath),
                'readable' => is_readable($fullPath),
                'admission_id' => $this->admission->id
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Error attaching admission letter', [
                'error' => $e->getMessage(),
                'path' => $this->pdfPath,
                'admission_id' => $this->admission->id
            ]);

            return [];
        }
    }

    /**
     * Handle a failed sending attempt.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to send admission approval email', [
            'error' => $exception->getMessage(),
            'admission_id' => $this->admission->id,
            'pdf_path' => $this->pdfPath
        ]);
    }
}
