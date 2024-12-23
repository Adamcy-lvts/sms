<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionExpiredNotification extends Notification
{
    use Queueable;

    protected $subscription;

    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $renewUrl = route('filament.sms.tenant.billing', [
            'tenant' => $this->subscription->school->slug
        ]);

        return (new MailMessage)
            ->subject('Subscription Expired')
            ->line('Your subscription has expired.')
            ->line('Plan: ' . $this->subscription->plan->name)
            ->line('Expired on: ' . $this->subscription->ends_at->format('F j, Y'))
            ->action('Renew Subscription', $renewUrl)
            ->line('Please renew your subscription to continue using all features.');
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Subscription Expired',
            'message' => 'Your subscription has expired. Please renew to continue using all features.',
            'subscription_id' => $this->subscription->id,
            'plan_name' => $this->subscription->plan->name,
            'expired_at' => $this->subscription->ends_at
        ];
    }
}