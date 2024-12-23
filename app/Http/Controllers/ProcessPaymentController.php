<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Agent;
use App\Models\School;
use App\Models\Payment;
use App\Models\SubsPayment;
use App\Models\AgentPayment;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Helpers\PaystackHelper;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelPdf\Facades\Pdf;
use App\Models\SubscriptionReceipt;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Mail;
use App\Mail\SubscriptionReceiptMail;
use App\Services\SubscriptionService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Redirect;
use Unicodeveloper\Paystack\Facades\Paystack;

class ProcessPaymentController extends Controller
{

    protected $subscriptionService;
    public $receipt;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }
    /**
     * Redirect the User to Paystack Payment Page
     * @return Url
     */

    public function redirectToGateway(Request $request)
    {
        $user = auth()->user();
        $school = $user->schools->first(); // Assume this returns the school associated with the user
        if ($request->planId) {
            $plan = Plan::find($request->planId);
        }

        $agent = $school->agent; // Assuming there's a direct relationship set up in the School model

        // Check if the school has had any subscriptions before
        $isNewSubscriber = !$school->subscriptions()->exists(); // true if no subscriptions exist

        // If the school is a new subscriber, create a local trial subscription

        if ($isNewSubscriber) {

            // Start the transaction
            DB::beginTransaction();

            try {
                $subscription = $school->subscriptions()->create([
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => now()->addDays($plan->duration),
                    'trial_ends_at' => now()->addDays($plan->trial_period ?? $plan->duration), // Assuming a 30-day trial period
                    'is_on_trial' => true,

                ]);
                // Assuming this is the part where you create the subscription and payment
                $subsPayment = SubsPayment::create([
                    'school_id' => $school->id,
                    'agent_id' => $agent->id ?? null, // Assuming $agent is defined
                    'amount' => 0.00, // Assuming the trial is free
                    'status' => 'paid',
                    'payment_date' => now(),
                ]);

                // If all operations were successful, commit the transaction
                DB::commit();

                // Continue with sending the receipt by email and other operations
                // $this->sendReceiptByEmail($receipt, $subsPayment, $subscription); // Assuming $subscription is defined
                Notification::make()
                    ->title('Success, You have been given a 30-day trial.')
                    ->success()
                    ->send();
                return redirect()->route('filament.sms.tenant', ['tenant' => $school->slug]); // Assuming $school is defined and has a slug
            } catch (\Exception $e) {
                // Rollback the transaction if any operation fails
                DB::rollBack();

                // Handle the exception (e.g., log the error and notify the user)
                // Log::error('Failed to process payment and create subscription: ' . $e->getMessage());
                Log::error('Failed to process payment and create subscription: ' . $e->getMessage(), [
                    'exception' => $e,
                    'request' => $request->all(),
                ]);
                Notification::make()
                    ->title("Failed to process payment and create subscription")
                    ->danger()
                    ->send();
                return redirect()->back()->withErrors('Failed to process payment and create subscription.');
            }
        }

        // Determine split payment details if applicable
        $splitData = null;


        if ($agent && $agent->subaccount_code) {
            $splitData = [
                "type" => "percentage",
                "currency" => "NGN",
                "subaccounts" => [
                    [
                        "subaccount" => $agent->subaccount_code,
                        "share" => $agent->percentage // Assuming the percentage is stored in the agent's model
                    ]
                ],
                "bearer_type" => "account", // Main account bears the transaction charges
                "main_account_share" => 100 - $agent->percentage // Calculate the main account's share
            ];
        }

        $metadata = json_encode([
            'schoolName' => $school->name,
            'schoolSlug' => $school->slug,
            'paymentType' => 'subscription',
            'planId' => $request->planId,
            'userId' => $user->id,
            'agentId' => optional($agent)->id, // Use optional helper to avoid errors if no agent
        ]);

        $data = [
            'amount' => $request->amount * 100, // Convert to kobo
            'email' => $request->email,
            'reference' => Paystack::genTranxRef(),
            'metadata' => $metadata,
            'plan' => $plan ? $plan->plan_code : null,
            'split' => $splitData ? json_encode($splitData) : null,

        ];

        try {
            $response = Paystack::getAuthorizationUrl($data)->redirectNow();
            return $response;
        } catch (\Exception $e) {
            Log::error('Payment initialization failed:', ['message' => $e->getMessage(), 'stack' => $e->getTraceAsString()]);
            return redirect()->back()->withErrors('Failed to initiate payment. Please try again.');
        }
    }


    public function handleGatewayCallback()
    {
        $paymentDetails = Paystack::getPaymentData();

        // Log::info('Payment Details:', $paymentDetails);

        // Check if the payment was successful
        if ($paymentDetails['data']['status'] !== 'success') {
            return redirect()->route('subscriptions')->withErrors('Payment failed. Please try again.');
        }

        $metadata = $paymentDetails['data']['metadata'];
        $schoolSlug = $metadata['schoolSlug'];
        $school = School::where('slug', $schoolSlug)->first();

        if (!$school) {
            Log::error('School not found.');
            return redirect()->route('subscriptions')->withErrors('Invalid school.');
        }

        Notification::make()
            ->title('Subscription Successful.')
            ->success()
            ->send();

        return redirect()->route('filament.sms.tenant', ['tenant' => $school->slug]);
    }

}
