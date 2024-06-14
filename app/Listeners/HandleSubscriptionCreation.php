<?php

namespace App\Listeners;

use DateTime;
use App\Models\Plan;
use App\Models\School;
use App\Models\SubsPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\SubscriptionReceiptMail;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\SubscriptionCreationStarted;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleSubscriptionCreation
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SubscriptionCreationStarted $event)
    {
         // Extracting payload data
         $data = json_decode(json_encode($payload['data']), false);

         // Extract necessary information
         $customerCode = $data->customer->customer_code ?? null;
         $planCode = $data->plan->plan_code ?? null;
         $subscriptionCode = $data->subscription_code ?? null;
         $schoolEmail = $data->customer->email ?? null;  // Adjust based on actual metadata location
         $nextPaymentDate = $data->next_payment_date ?? null;
 
 
         $dateTime = new DateTime($data->next_payment_date);
         $formattedDate = $dateTime->format('Y-m-d H:i:s');
 
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
                     'next_payment_date' => $formattedDate
                 ]);
 
                 // Create new subscription
                 $subscription = $this->createSubscription($school, $plan, $subscriptionCode, $formattedDate);
             } else {
                 // Renewal or new subscription
                 $subscription = $subscription ? $subscription->update([
                     'ends_at' => now()->addDays($plan->duration),
                     'subscription_code' => $subscriptionCode,
                     'next_payment_date' => $formattedDate
                 ]) : $this->createSubscription($school, $plan, $subscriptionCode, $formattedDate);
             }
 
           
 
             DB::commit();
             Log::info('Subscription created or updated successfully.');
 
 
             $latestPayment = SubsPayment::where('school_id', $school->id)->latest('created_at')->first();
             Log::info('Latest Subscription payment: ' . $latestPayment);
             $paymentCount = SubsPayment::where('school_id', $school->id)->count();
             Log::info('Number of payments for school: ' . $paymentCount);
 
 
             // Retrieve the latest payment for the school
         
             // $latestPayment = SubsPayment::where('school_id', $school->id)->latest()->first();
             Log::info('Latest Subscription payment: ' . $latestPayment);
             if ($latestPayment) {
                 // Update the payment record with the subscription id
                 $latestPayment->update(['subscription_id' => $subscription->id]);
             }
 
             // Send email receipt to the school
             Mail::to($school->email)->send(new SubscriptionReceiptMail($subscription));
         } catch (\Exception $e) {
             DB::rollBack();
             Log::error('Subscription processing failed: ' . $e->getMessage());
         }
    }
}
