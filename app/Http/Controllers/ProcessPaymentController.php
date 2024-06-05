<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Unicodeveloper\Paystack\Facades\Paystack;

class ProcessPaymentController extends Controller
{
    /**
     * Redirect the User to Paystack Payment Page
     * @return Url
     */
    // public function redirectToGateway()
    // {
    //   dd(request()->all());
    //     try {
    //         return paystack()->getAuthorizationUrl()->redirectNow();
    //     } catch (\Exception $e) {
    //         return Redirect::back()->withMessage(['msg' => 'The paystack token has expired. Please refresh the page and try again.', 'type' => 'error']);
    //     }
    // }

    public function redirectToGateway(Request $request)
    {
        $user = auth()->user();
        $school = $user->schools->first(); // Assume this returns the school associated with the user

        $agent = $school->agent; // Assuming there's a direct relationship set up in the School model

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
                "bearer_type" => "all", // Main account bears the transaction charges
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
            'split' => $splitData ? json_encode($splitData) : null
        ];

        try {
            $response = Paystack::getAuthorizationUrl($data)->redirectNow();
            return $response;
        } catch (\Exception $e) {
            Log::error('Payment initialization failed:', ['message' => $e->getMessage(), 'stack' => $e->getTraceAsString()]);
            return redirect()->back()->withErrors('Failed to initiate payment. Please try again.');
        }
    }


    /**
     * Obtain Paystack payment information
     * @return void
     */
    public function handleGatewayCallback()
    {
        $paymentDetails = Paystack()->getPaymentData();

        dd($paymentDetails);
        
        // Now you have the payment details,
        // you can store the authorization_code in your db to allow for recurrent subscriptions
        // you can then redirect or do whatever you want
    }
}
