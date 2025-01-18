<?php

namespace App\Notifications;

use App\Models\School;
use Illuminate\Bus\Queueable;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;

class TrialExpirationNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    private $school;
    private $daysLeft;

    public function __construct(School $school, int $daysLeft)
    {
        $this->school = $school;
        $this->daysLeft = $daysLeft;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Trial Expiration Notice - {$this->school->name}")
            ->greeting("Hello {$notifiable->first_name}")
            ->line("The trial period for {$this->school->name} will expire in {$this->daysLeft} days.")
            ->line("To maintain uninterrupted access to all features, please upgrade to a paid subscription.")
            ->action('View Subscription Plans', route('filament.sms.pages.pricing-page', [
                'tenant' => $this->school->slug
            ]));
    }

    public function toDatabase($notifiable): array
    {
        return Notification::make()
            ->warning()
            ->title('Trial Period Ending Soon')
            ->body("Trial for {$this->school->name} expires in {$this->daysLeft} days")
            ->actions([
                \Filament\Notifications\Actions\Action::make('view_plans')
                    ->button()
                    ->label('View Plans')
                    ->url(route('filament.sms.pages.pricing-page', [
                        'tenant' => $this->school->slug
                    ])),
            ])
            ->getDatabaseMessage();
    }
}
