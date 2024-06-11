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
use Illuminate\Support\Facades\Mail;
use App\Mail\SubscriptionReceiptMail;
use App\Services\SubscriptionService;
use Illuminate\Queue\SerializesModels;
use Filament\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

class ProcessPaystackWebhookJob extends ProcessWebhookJob
{
    public $school;

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
        // Decode the payload data into an object
        $data = json_decode(json_encode($payload['data']), false);

        // Log the metadata for debugging purposes
        // log::info('Metadata Content:', ['metadata' => json_encode($data->metadata)]);

        // Check if the payment metadata indicates a subscription payment
        if (isset($data->metadata->paymentType) && $data->metadata->paymentType === 'subscription') {
            // log::info('Payment detected as subscription.');
            return true;
        } else {
            // log::info('Payment detected as non-subscription.');
            return false;
        }
    }


    protected function handleSubscriptionPayment($payload)
    {
        // Ensure payload is an object first
        $data = json_decode(json_encode($payload['data']), false);

        // Accessing metadata safely
        $metadata = $data->metadata ?? null;
        $schoolSlug = $metadata->schoolSlug ?? null;
        $planCode = $data->plan->plan_code ?? null;
        $agentId = $metadata->agentId ?? null;

        $this->school = School::where('slug', $schoolSlug)->first();
        $plan = Plan::where('plan_code', $planCode)->first();
        $agent = Agent::find($agentId);

        // Log the retrieved school and plan information
        // Log::info('Retrieved School:', ['school' => $school ? $school->toArray() : 'Not found']);
        // Log::info('Retrieved Plan:', ['plan' => $plan ? $plan->toArray() : 'Not found']);

        if (!$this->school || !$plan) {
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
            'school_id' => $this->school->id,
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
                'school_id' => $this->school->id,
                'amount' => $agentAmount,
                'split_code' => $data->split->split_code,
                'status' => 'paid',
                'payment_date' => now(),
            ]);
        }

        Log::info('Subscription payment processed successfully', ['school_id' => $this->school->id]);
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

        // Extract necessary information
        $customerCode = $data->customer->customer_code ?? null;
        $planCode = $data->plan->plan_code ?? null;
        $subscriptionCode = $data->subscription_code ?? null;
        $schoolEmail = $data->customer->email ?? null;  // Adjust based on actual metadata location

        // Retrieve school and plan based on provided codes
        $school = School::where('email', $schoolEmail)->firstOrFail();
        $plan = Plan::where('plan_code', $planCode)->firstOrFail();

        Log::info('Handling Subscription Creation', [

            'planCode' => $planCode,
            'customerCode' => $customerCode,
            'subscriptionCode' => $subscriptionCode,
            'schoolDetails' => $school ? $school : 'School not found',
        ]);
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

            // Send email receipt to the school
            Mail::to($school->email)->send(new SubscriptionReceiptMail($subscription));
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
