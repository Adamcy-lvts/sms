<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\School;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    /**
     * Handles creating or updating a school's subscription.
     *
     * @param School $school
     * @param Plan $plan
     * @param array $paymentDetails
     * @return Subscription
     */
    public function manageSubscription(School $school, Plan $plan, array $paymentDetails)
    {
        $subscription = $school->subscriptions()->where('status', 'active')->first();

        DB::beginTransaction();
        try {
            if ($subscription) {
                if ($subscription->plan_id != $plan->id) {
                    // This is an upgrade or downgrade
                    $subscription->update([
                        'status' => 'inactive', // Mark previous subscription as inactive
                        'ends_at' => now(), // Set the end time to now
                    ]);

                    // Create new subscription
                    $subscription = $this->createSubscription($school, $plan, $paymentDetails);
                } else {
                    // This is a renewal
                    $subscription->update([
                        'ends_at' => now()->addDays($plan->duration), // Extend the subscription
                    ]);
                }
            } else {
                // New subscription
                $subscription = $this->createSubscription($school, $plan, $paymentDetails);
            }

            DB::commit();
            return $subscription;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Failed to manage subscription: " . $e->getMessage());
        }
    }

    /**
     * Creates a new subscription record.
     *
     * @param School $school
     * @param Plan $plan
     * @param array $paymentDetails
     * @return Subscription
     */
    private function createSubscription(School $school, Plan $plan, array $paymentDetails)
    {
        try {
            // Convert payment details to JSON string
            $paymentDetailsJson = json_encode($paymentDetails);

            // Create the subscription in the database
            $subscription = $school->subscriptions()->create([
                'plan_id' => $plan->id,
                'school_id' => $school->id,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addDays($plan->duration),
                'is_recurring' => $plan->is_recurring, // Assuming you have this attribute in Plan
              
                // Initialize other fields as necessary
            ]);

            // Optionally log the successful creation
            Log::info('Subscription created successfully', ['school_id' => $school->id, 'subscription_id' => $subscription->id]);

            return $subscription;
        } catch (\Exception $exception) {
            // Log error details
            Log::error('Failed to create subscription', [
                'error' => $exception->getMessage(),
                'school_id' => $school->id,
                'plan_id' => $plan->id,
            ]);

            // Consider rethrowing the exception or handling it as per your application's needs
            throw $exception;
        }
    }

}
