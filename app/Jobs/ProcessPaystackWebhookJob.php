<?php

namespace App\Jobs;

use App\Models\Plan;
use App\Models\Agent;
use App\Models\School;
use App\Models\Payment;
use App\Models\SubsPayment;
use App\Models\AgentPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\SubscriptionService;
use Illuminate\Queue\SerializesModels;
use Filament\Notifications\Notification;
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
        Log::info('Webhook job instantiated', ['webhookCall' => $webhookCall]);
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info($this->webhookCall);
        $payload = $this->webhookCall->payload;
        $eventType = $payload['event'] ?? null;
  

        switch ($eventType) {
            case 'charge.success':
                if ($this->isSubscriptionPayment($payload)) {
                    $this->handleSubscriptionPayment($payload);
                } else {
                    $this->handleNonSubscriptionPayment($payload);
                }
                break;
            case 'subscription.create':
                $this->handleSubscriptionCreation($payload);
                break;
            default:
                Log::info("Received unhandled Paystack event: {$eventType}");
        }
    }

    protected function isSubscriptionPayment($payload)
    {
        // Check if the payment metadata indicates a subscription payment
        return isset($payload['data']['metadata']['paymentType']) && 
               $payload['data']['metadata']['paymentType'] === 'subscription';
    }

    protected function handleSubscriptionPayment($payload)
    {
        $metadata = $payload['data']['metadata'];
        $schoolSlug = $metadata['schoolSlug'];
        $planCode = $metadata['plan_code']; // Adjust according to Paystack's response structure
        $agentId = $metadata['agentId'] ?? null;

        $school = School::where('slug', $schoolSlug)->first();
        $plan = Plan::where('paystack_plan_code', $planCode)->first();
        $agent = Agent::find($agentId);

        if (!$school || !$plan) {
            Log::error('Invalid school or plan for subscription.');
            return;
        }

        $totalAmount = $payload['data']['amount'] / 100;
        $transactionFee = $totalAmount * 0.015;
        $netAmount = $totalAmount - $transactionFee;

        $agentAmount = 0;
        $splitCode = $payload['data']['split']['split_code'] ?? null;

        if ($agent && $splitCode) {
            $agentAmount = ($payload['data']['split']['shares']['subaccounts'][0]['amount'] ?? 0) / 100;
            $netAmount -= $agentAmount;
        }

        SubsPayment::create([
            'school_id' => $school->id,
            'agent_id' => $agent->id ?? null,
            'plan_id' => $plan->id,
            'amount' => $totalAmount,
            'net_amount' => $netAmount,
            'split_amount_agent' => $agentAmount,
            'split_code' => $splitCode,
            'reference' => $payload['data']['reference'],
            'status' => 'paid',
            'payment_date' => now(),
        ]);

        if ($agent) {
            AgentPayment::create([
                'agent_id' => $agent->id,
                'school_id' => $school->id,
                'amount' => $agentAmount,
                'split_code' => $splitCode,
                'status' => 'paid',
                'payment_date' => now(),
            ]);
        }

        Log::info('Subscription payment processed successfully', ['school_id' => $school->id]);
    }

    protected function handleNonSubscriptionPayment($payload)
    {
        Payment::create([
            'amount' => $payload['data']['amount'] / 100,
            'status' => 'paid',
            'payment_date' => now(),
            'type' => $payload['data']['metadata']['paymentType'] ?? 'general',
            'reference' => $payload['data']['reference']
        ]);

        Log::info('Non-subscription payment recorded successfully.');
    }

    protected function handleSubscriptionCreation($payload)
    {
        $data = $payload['data'];
        $schoolSlug = $data['metadata']['schoolSlug'];  // Assuming schoolSlug is sent in metadata
        $planCode = $data['plan']['plan_code'];
        $customerCode = $data['customer']['customer_code'];
        $subscriptionCode = $data['subscription_code'];

        $school = School::where('slug', $schoolSlug)->first();
        $plan = Plan::where('paystack_plan_code', $planCode)->first();

        if (!$school || !$plan) {
            Log::error('Invalid school or plan.');
            return;
        }

        $school->update(['customer_code' => $customerCode]);

        DB::beginTransaction();
        try {
            Log::info('Subscription created successfully', ['data' => $payload['data']]);
            $subscription = $school->subscriptions()->where('status', 'active')->first();

            if ($subscription && $subscription->plan_id != $plan->id) {
                // This is an upgrade or downgrade
                $subscription->update([
                    'status' => 'inactive',
                    'ends_at' => now(),
                ]);

                // Create new subscription
                $subscription = $this->createSubscription($school, $plan, $data, $subscriptionCode);
            } else {
                // Renewal or new subscription
                $subscription = $subscription ? $subscription->update([
                    'ends_at' => now()->addDays($plan->duration),
                    'subscription_code' => $subscriptionCode,
                ]) : $this->createSubscription($school, $plan, $data, $subscriptionCode);
            }

            DB::commit();
           
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Subscription processing failed: ' . $e->getMessage());
           
        }
    }

    private function createSubscription($school, $plan, $data, $subscriptionCode)
    {
        return $school->subscriptions()->create([
            'plan_id' => $plan->id,
            'school_id' => $school->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addDays($plan->duration),
            'subscription_code' => $subscriptionCode,
            // 'payment_details' => json_encode($data),
        ]);
    }

    
}
