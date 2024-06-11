<?php

namespace App\Jobs;

use stdClass;
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

    public function handle()
    {
        
        $payload = $this->webhookCall->payload;
        $eventType = $payload['event'] ?? null;

        // Log::info('Payload received', ['payload' => $payload]);
        // Log::info('Event type determined', ['event' => $eventType]);

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
        $data = json_decode(json_encode($payload['data']), false);
        Log::info('Payload Data', ['data' => json_encode($data)]);

        log::info('check type'. $data->metadata->paymentType);
        // Check if the payment metadata indicates a subscription payment
        return isset($data->metadata->paymentType) &&
            $data->metadata->paymentType === 'subscription';
    }

    protected function handleSubscriptionPayment($payload)
    {
        // Ensure payload is an object first
        $data = json_decode(json_encode($payload['data']), false);
        // Log::info('Payload Data', ['data' => json_encode($data)]);

        // Accessing metadata safely
        $metadata = $data->metadata ?? null;
        log::info('metadata'.' '.$metadata);
        $schoolSlug = $metadata->schoolSlug ?? null;
        $planCode = $data->plan->plan_code ?? null;
        $agentId = $metadata->agentId ?? null;
      
        $school = School::where('slug', $schoolSlug)->first();
        $plan = Plan::where('paystack_plan_code', $planCode)->first();
        $agent = Agent::find($agentId);

        if (!$school || !$plan) {
            Log::error('Invalid school or plan for subscription.');
            return;
        }

        $totalAmount = $data->amount / 100; // Converting kobo to Naira
        $transactionFee = $totalAmount * 0.015; // Calculate the transaction fee
        $netAmount = $totalAmount - $transactionFee;

        $agentAmount = 0;
        if (isset($data->split) && $data->split->split_code) {
            $agentAmount = ($data->split->shares->subaccounts[0]->amount ?? 0) / 100;
            $netAmount -= $agentAmount;
        }

        SubsPayment::create([
            'school_id' => $school->id,
            'agent_id' => $agent->id ?? null,
            'plan_id' => $plan->id,
            'amount' => $totalAmount,
            'net_amount' => $netAmount,
            'split_amount_agent' => $agentAmount,
            'split_code' => $data->split->split_code,
            'reference' => $data->reference,
            'status' => 'paid',
            'payment_date' => now(),
        ]);

        if ($agent) {
            AgentPayment::create([
                'agent_id' => $agent->id,
                'school_id' => $school->id,
                'amount' => $agentAmount,
                'split_code' => $data->split->split_code,
                'status' => 'paid',
                'payment_date' => now(),
            ]);
        }

        Log::info('Subscription payment processed successfully', ['school_id' => $school->id]);
    }



    protected function handleNonSubscriptionPayment($payload)
    {
        $data = json_decode(json_encode($payload['data']), false);
        // Log::info('payment payload: ' . $data);
        Payment::create([
            'amount' => $data->amount / 100,
            'status' => 'paid',
            'payment_date' => now(),
            'type' => $payload->data->metadata->paymentType ?? 'general',
            'reference' => $data->reference
        ]);

        Log::info('Non-subscription payment recorded successfully.');
    }

    protected function handleSubscriptionCreation($payload)
    {
        // Extracting payload data
        $data = json_decode(json_encode($payload['data']), false);
        // Log::info('subscription payload: ' . $data);
        // Extract necessary information
        $customerCode = $data->customer->customer_code ?? null;
        $planCode = $data->plan->plan_code ?? null;
        $subscriptionCode = $data->subscription_code ?? null;
        $schoolSlug = $data->customer->metadata->schoolSlug ?? null;  // Adjust based on actual metadata location

        // Retrieve school and plan based on provided codes
        $school = School::where('slug', $schoolSlug)->first();
        $plan = Plan::where('plan_code', $planCode)->first();

        if (!$school || !$plan) {
            Log::error('Invalid school or plan.');
            return;
        }

        // Update school with customer code
        $school->update(['customer_code' => $customerCode]);

        // Handle database operations within a transaction
        DB::beginTransaction();
        try {
            // Check if an active subscription exists
            $subscription = $school->subscriptions()->where('status', 'active')->first();

            if ($subscription && $subscription->plan_id != $plan->id) {
                // This is an upgrade or downgrade
                $subscription->update([
                    'status' => 'inactive',
                    'ends_at' => now(),
                ]);

                // Create new subscription
                $subscription = $this->createSubscription($school, $plan, $subscriptionCode);
            } else {
                // Renewal or new subscription
                $subscription = $subscription ? $subscription->update([
                    'ends_at' => now()->addDays($plan->duration),
                    'subscription_code' => $subscriptionCode,
                ]) : $this->createSubscription($school, $plan, $subscriptionCode);
            }

            DB::commit();
            Log::info('Subscription created or updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Subscription processing failed: ' . $e->getMessage());
        }
    }

    private function createSubscription($school, $plan, $subscriptionCode)
    {
        return $school->subscriptions()->create([
            'plan_id' => $plan->id,
            'school_id' => $school->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addDays($plan->duration),
            'subscription_code' => $subscriptionCode,
        ]);
    }

}
