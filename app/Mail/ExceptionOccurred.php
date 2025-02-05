<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ExceptionOccurred extends Mailable
{
    use SerializesModels;

    public array $exceptionData;

    /**
     * Create a new message instance.
     */
    public function __construct(protected Throwable $exception)
    {
        // Collect all relevant exception data
        $this->exceptionData = $this->collectExceptionData();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Exception Occurred: ' . get_class($this->exception),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.exception-occurred',
            with: [
                'data' => $this->exceptionData,
                'exception' => $this->exception,
            ],
        );
    }

    /**
     * Collect detailed exception data for the email
     */
    protected function collectExceptionData(): array
    {
        return [
            'message' => $this->exception->getMessage(),
            'file' => $this->exception->getFile(),
            'line' => $this->exception->getLine(),
            'trace' => $this->exception->getTraceAsString(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'user' => auth()->user() ? [
                'id' => auth()->id(),
                'email' => auth()->user()->email,
                'name' => auth()->user()->name ?? 'N/A'
            ] : 'Guest',
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'environment' => app()->environment(),
            'server' => [
                'server_name' => $_SERVER['SERVER_NAME'] ?? 'N/A',
                'server_address' => $_SERVER['SERVER_ADDR'] ?? 'N/A',
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
            ],
            'request' => [
                'headers' => request()->headers->all(),
                'params' => request()->all(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]
        ];
    }
}