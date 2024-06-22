<?php

namespace App\Models;

use App\Models\Agent;
use App\Models\School;
use App\Models\Subscription;
use App\Models\PaymentMethod;
use Spatie\LaravelPdf\Facades\Pdf;
use App\Models\SubscriptionReceipt;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Mail;
use App\Mail\SubscriptionReceiptMail;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubsPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'agent_id', 'amount', 'net_amount', 'split_amount_agent',
        'split_code', 'status', 'payment_method_id', 'reference', 'payment_date', 'subscription_id'
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($payment) {
            // Check for an active subscription at the moment of payment creation
            $activeSubscription = $payment->school->subscriptions()
                ->where('status', 'active')
                ->latest('created_at')
                ->first();

            if ($activeSubscription) {
                // Link the payment to the active subscription
                $payment->subscription_id = $activeSubscription->id;
                $payment->save();
            }

            $school = $payment->school;
            $plan = $activeSubscription->plan;

            $receipt = $payment->subscriptionReceipt()->create([
                'payment_id' => $payment->id,
                'school_id' => $school->id,
                'payment_date' => $payment->payment_date,
                'receipt_for' => 'subscription', // Assuming 'Subscription' is the type for subscription payments
                'amount' => $plan->price, // Assuming $plan is defined
                'receipt_number' => SubscriptionReceipt::generateReceiptNumber($payment->payment_date),
                // 'remarks' and 'qr_code' can be set here if needed
            ]);

            // Send the receipt to the school via email
            $payment->sendReceiptByEmail($receipt, $payment, $activeSubscription);
        });
    }

    public function sendReceiptByEmail($receipt, $subsPayment, $subscription): void
    {
        try {
            $payment = $subsPayment;
            $school = $payment->school;

            $pdf = $payment->school->name . '_' . now() . '_' . 'receipt.pdf';
            log::info($subsPayment);
            $receiptPath = storage_path("app/{$pdf}");
            log::info($payment);
            // Generate the PDF receipt
            Pdf::view('pdfs.subscription_receipt_pdf', [
                'payment' => $payment,
                'receipt' => $receipt
            ])->withBrowsershot(function (Browsershot $browsershot) {
                $browsershot->setChromePath(config('app.chrome_path'));
            })->save($receiptPath);

            // Check if the user has an email address
            if (!empty($payment->school->email)) {

                // Send email receipt to the school
                Mail::to($school->email)->send(new SubscriptionReceiptMail($subscription, $subsPayment, $receipt, $pdf, $receiptPath));

                // Notify the user that the receipt has been sent successfully
                Notification::make()
                    ->title('Receipt has been sent to your email.')
                    ->success()
                    ->send();
            } else {
                // Notify the user that the customer doesn't have a valid email
                Notification::make()
                    ->title('Failed to send deposit receipt! Customer does not have an email address.')
                    ->warning()
                    ->send();
            }
        } catch (\Exception $e) {
            // Log any exceptions that may arise during this process
            Log::error("Error sending receipt: {$e->getMessage()}");
            Log::error("Error sending receipt: {$e->getFile()} (Line: {$e->getLine()})");
            Log::error("Error sending receipt: {$e->getTraceAsString()}");

            // Notify the user about the error
            Notification::make()
                ->title('Failed to send deposit receipt! Please try again later or send manually.')
                ->danger()
                ->send();
        }
    }

    public function paymentMethod()
    {

        return $this->belongsTo(PaymentMethod::class);
    }

    public function SubscriptionReceipt()
    {
        return $this->hasOne(SubscriptionReceipt::class, 'payment_id');
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }
}
