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
use Filament\Facades\Filament;
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

        return redirect()->route('filament.sms.tenant', ['tenant' => Filament::getTenant()->slug]);
    }

}
