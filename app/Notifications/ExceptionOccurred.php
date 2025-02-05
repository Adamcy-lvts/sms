<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

class ExceptionOccurred extends Notification
{
    public function __construct(
        public string $message,
        public string $file,
        public int $line,
        public string $trace,
        public string $url,
        public string $method,
        public string $ip,
        public ?array $user, // Add user details
        public ?array $school // Add school parameter
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Exception: " . config('app.name'))
            ->line("Message: {$this->message}")
            ->line("File: {$this->file}")
            ->line("Line: {$this->line}")
            ->line("URL: {$this->url}")
            ->line("Method: {$this->method}")
            ->line("IP: {$this->ip}");

        if ($this->user) {
            $mail->line('User Details:')
                ->line('- ID: ' . ($this->user['id'] ?? 'N/A'))
                ->line('- Name: ' . ($this->user['first_name'] . ' ' . $this->user['last_name'] ?? 'N/A'))
                ->line('- Email: ' . ($this->user['email'] ?? 'Not authenticated'));
        }

        if ($this->school) {
            $mail->line('School Details:')
                ->line('- ID: ' . ($this->school['id'] ?? 'N/A'))
                ->line('- Name: ' . ($this->school['name'] ?? 'N/A'));
        }

        return $mail->line("Stack Trace:")
            ->line($this->trace);
    }
}
