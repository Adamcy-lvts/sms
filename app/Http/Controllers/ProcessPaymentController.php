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

                // $receipt = $subsPayment->subscriptionReceipt()->create([
                //     'payment_id' => $subsPayment->id,
                //     'school_id' => $school->id,
                //     'payment_date' => $subsPayment->payment_date,
                //     'receipt_for' => 'subscription', // Assuming 'Subscription' is the type for subscription payments
                //     'amount' => $plan->price, // Assuming $plan is defined
                //     'receipt_number' => SubscriptionReceipt::generateReceiptNumber($subsPayment->payment_date),
                //     // 'remarks' and 'qr_code' can be set here if needed
                // ]);

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

    // private function sendReceiptByEmail($receipt, $subsPayment, $subscription): void
    // {
    //     try {
    //         $payment = $subsPayment;
    //         $school = $payment->school;

    //         $pdf = $payment->school->name . '_' . now() . '_' . 'receipt.pdf';

    //         $receiptPath = storage_path("app/{$pdf}");

    //         // Generate the PDF receipt
    //         Pdf::view('pdfs.subscription_receipt_pdf', [
    //             'payment' => $payment,
    //             'receipt' => $receipt
    //         ])->withBrowsershot(function (Browsershot $browsershot) {
    //             $browsershot->setChromePath(config('app.chrome_path'));
    //         })->save($receiptPath);

    //         // Check if the user has an email address
    //         if (!empty($payment->school->email)) {

    //             // Send email receipt to the school
    //             Mail::to($school->email)->send(new SubscriptionReceiptMail($subscription, $subsPayment, $receipt, $pdf, $receiptPath));

    //             // Notify the user that the receipt has been sent successfully
    //             Notification::make()
    //                 ->title('Receipt has been sent to your email.')
    //                 ->success()
    //                 ->send();
    //         } else {
    //             // Notify the user that the customer doesn't have a valid email
    //             Notification::make()
    //                 ->title('Failed to send deposit receipt! Customer does not have an email address.')
    //                 ->warning()
    //                 ->send();
    //         }
    //     } catch (\Exception $e) {
    //         // Log any exceptions that may arise during this process
    //         Log::error("Error sending receipt: {$e->getMessage()}");

    //         // Notify the user about the error
    //         Notification::make()
    //             ->title('Failed to send deposit receipt! Please try again later or send manually.')
    //             ->danger()
    //             ->send();
    //     }
    // }

    public function handleGatewayCallback()
    {
        $paymentDetails = Paystack::getPaymentData();

        Log::info('Payment Details:', $paymentDetails);

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


    /**
     * Obtain Paystack payment information
     * @return void
     */
    // public function handleGatewayCallback()
    // {
    //     $paymentDetails = Paystack::getPaymentData();

    //     // Check if payment is successful and paymentType is subscription
    //     if ($paymentDetails['data']['status'] === 'success' && $paymentDetails['data']['metadata']['paymentType'] === 'subscription') {
    //         // Retrieve important metadata
    //         $metadata = $paymentDetails['data']['metadata'];
    //         $schoolId = $metadata['schoolId'];
    //         $planId = $metadata['planId'];
    //         $agent = Agent::find($metadata['agent_id'] ?? null);

    //         // Find the school and plan based on IDs provided
    //         $school = School::find($schoolId);
    //         $plan = Plan::find($planId);

    //         //lets check if it's a split payment and if it is? let's let's make payment entry into the subspayment table and agentpayment table
    //         // Payment calculations
    //         $totalAmount = $paymentDetails['data']['amount'] / 100; // Convert from kobo to Naira
    //         $transactionFee = $totalAmount * 0.015; // Assuming 1.5% transaction fee
    //         $netAmount = $totalAmount - $transactionFee;

    //         $splitCode = null;

    //         $agentAmount = 0;
    //         if ($agent && isset($paymentDetails['data']['split'])) {
    //             $agentAmount = ($paymentDetails['data']['split']['subaccounts'][0]['amount'] ?? 0) / 100;
    //             $splitCode = $paymentDetails['data']['split']['split_code'] ?? null;
    //             $netAmount -= $agentAmount; // Deduct agent's share
    //         }
    //         // Ensure both school and plan are found
    //         if ($school && $plan) {
    //             // Record payment details in SubsPayments table
    //             $subsPayment = SubsPayment::create([
    //                 'school_id' => $school->id,
    //                 'plan_id' => $plan->id,
    //                 'amount' => $totalAmount,
    //                 'net_amount' => $netAmount,
    //                 'split_amount_agent' => $agentAmount,
    //                 'split_code' => $splitCode,
    //                 'status' => 'paid',
    //                 'payment_date' => now(),
    //             ]);

    //             // If there's a split payment and an agent is involved
    //             if (isset($agent)) {
    //                 if ($agent) {
    //                     AgentPayment::create([
    //                         'agent_id' => $agent->id,
    //                         'school_id' => $school->id,
    //                         'amount' => $agentAmount,
    //                         'status' => 'paid',
    //                         'payment_date' => now(),
    //                     ]);
    //                 }
    //             }

    //             // Send email receipt to the school
    //             // Mail::to($school->email)->send(new SubscriptionReceiptMail($subsPayment));

    //             // Create or update the subscription


    //             // Redirect user to dashboard or intended page
    //             return redirect()->route('dashboard')->with('success', 'Subscription processed successfully.');
    //         }
    //     } else {
    //         // Handle failed payment scenario
    //         return redirect()->route('subscriptions')->withErrors('Payment failed. Please try again.');
    //     }
    // }
    // public function handleGatewayCallback()
    // {
    //     $paymentDetails = Paystack::getPaymentData();

    //     $metadata = $paymentDetails['data']['metadata'];

    //     $schoolSlug = $metadata['schoolSlug'];

    //     $school = School::where('slug', $schoolSlug)->first();

    //     Notification::make()
    //         ->title('Subscription Successfull.')
    //         ->success()
    //         ->send();

    //     return redirect()->route('filament.sms.tenant', ['tenant' => $school->slug]);

    //     Extract metadata
    //     $metadata = $paymentDetails['data']['metadata'];
    //     $paymentType = $metadata['paymentType'] ?? null;

    //     if ($paymentDetails['data']['status'] !== 'success') {
    //         return redirect()->route('subscriptions')->withErrors('Payment failed. Please try again.');
    //     }

    //     // If the payment is not for a subscription, handle it separately
    //     if ($paymentType !== 'subscription') {
    //         return $this->handleNonSubscriptionPayment($paymentDetails);
    //     }

    //     return $this->handleSubscriptionPayment($paymentDetails);
    // }

    // protected function handleNonSubscriptionPayment($paymentDetails)
    // {
    //     // Assuming non-subscription payments are to be logged only
    //     Payment::create([
    //         'amount' => $paymentDetails['data']['amount'] / 100,
    //         'status' => 'paid',
    //         'payment_date' => now(),
    //         'type' => $paymentDetails['data']['metadata']['paymentType'] ?? 'general',
    //         'reference' => $paymentDetails['data']['reference']
    //     ]);

    //     return redirect()->route('dashboard')->with('success', 'Payment recorded successfully.');
    // }

    // protected function handleSubscriptionPayment($paymentDetails)
    // {
    //     $metadata = $paymentDetails['data']['metadata'];
    //     $schoolSlug = $metadata['schoolSlug'];
    //     $planId = $metadata['planId'];
    //     $agent = Agent::find($metadata['agentId'] ?? null);

    //     $school = School::where('slug', $schoolSlug)->first();
    //     $plan = Plan::find($planId);

    //     if (!$school || !$plan) {
    //         return redirect()->route('subscriptions')->withErrors('Invalid school or plan.');
    //     }

    //     $totalAmount = $paymentDetails['data']['amount'] / 100;
    //     $transactionFee = $totalAmount * 0.015;
    //     $netAmount = $totalAmount - $transactionFee;

    //     $agentAmount = 0;
    //     $splitCode = $paymentDetails['data']['split']['split_code'] ?? null;

    //     if ($agent && isset($paymentDetails['data']['split'])) {
    //         $agentAmount = ($paymentDetails['data']['split']['shares']['subaccounts'][0]['amount'] ?? 0) / 100;
    //         $netAmount -= $agentAmount;
    //     }

    //     $customerCode = $paymentDetails['data']['customer']['customer_code'];


    //     SubsPayment::create([
    //         'school_id' => $school->id,
    //         'agent_id' => $agent->id ?? null,
    //         'plan_id' => $plan->id,
    //         'amount' => $totalAmount,
    //         'net_amount' => $netAmount,
    //         'split_amount_agent' => $agentAmount,
    //         'split_code' => $splitCode,
    //         'reference' => $paymentDetails['data']['reference'],
    //         'status' => 'paid',
    //         'payment_date' => now(),
    //     ]);

    //     if ($agent) {
    //         AgentPayment::create([
    //             'agent_id' => $agent->id,
    //             'school_id' => $school->id,
    //             'amount' => $agentAmount,
    //             'split_code' => $splitCode,
    //             'status' => 'paid',
    //             'payment_date' => now(),
    //         ]);
    //     }

    //     // Mail::to($school->email)->send(new SubscriptionReceiptMail($school));
    //     try {
    //         $subscription = $this->subscriptionService->manageSubscription($school, $plan, $paymentDetails);
    //         // Redirect with success message
    //         Notification::make()
    //             ->title('Subscription Successfull.')
    //             ->success()
    //             ->send();

    //         return redirect()->route('filament.sms.tenant', ['tenant' => $school->slug]);
    //     } catch (\Exception $e) {
    //         // Handle the exception
    //         Notification::make()
    //             ->title('Subscription processing failed.')
    //             ->danger()
    //             ->send();
    //         return redirect()->route('filament.sms.tenant', ['tenant' => $school->slug]);
    //     }

    //     // return redirect()->route('dashboard')->with('success', 'Subscription processed successfully.');
    // }
}
