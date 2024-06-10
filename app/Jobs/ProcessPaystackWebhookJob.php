<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

class ProcessPaystackWebhookJob extends ProcessWebhookJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct($webhookCall)
    {
        $this->webhookCall = $webhookCall;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Retrieve the incoming webhook payload
        $payload = $this->webhookCall->payload;

        // Determine the type of webhook event (based on the payload structure from Paystack)
        $eventType = $payload['event'] ?? null;

        // Handle different event types
        switch ($eventType) {
            case 'charge.success':
                $this->handlePaymentSuccess($payload);
                break;
            case 'subscription.create':
                $this->handleSubscriptionCreation($payload);
                break;
                // add more cases for other Paystack events you want to handle
            default:
                Log::info("Received unhandled Paystack event: {$eventType}");
        }
    }

    protected function handlePaymentSuccess($payload)
    {
        dd($payload);
    }

    protected function handleSubscriptionCreation($payload)
    {
        // Logic for handling subscription creation
    }
}
