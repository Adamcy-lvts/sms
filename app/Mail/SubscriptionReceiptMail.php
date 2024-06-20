<?php

namespace App\Mail;

use Carbon\Carbon;
use App\Models\SubsPayment;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Models\SubscriptionReceipt;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Contracts\Queue\ShouldQueue;

class SubscriptionReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subscription;
    public $subsPayment;
    public $receipt;
    public $pdf;
    public $receiptPath;
    public $trialEndsAt;
    /**
     * Create a new message instance.
     */
    public function __construct(Subscription $subscription, SubsPayment $subsPayment, SubscriptionReceipt $receipt, $pdf, $receiptPath)
    {
        $this->subscription = $subscription;
        $this->subsPayment = $subsPayment;
        $this->receipt = $receipt;
        $this->pdf = $pdf;
        $this->receiptPath = $receiptPath;
        $this->trialEndsAt = Carbon::parse($subscription->trial_ends_at);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('lv4mj1@gmail.com', 'SMS'),
            subject: 'Subscription Receipt',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $subsPlan = $this->subscription->plan->name;
        return new Content(
            markdown: 'emails.subscription_receipt_email',  
            with: [
                'urlToReceipt' => route('receipt.show', ['payment' => $this->subsPayment->id, 'receipt' => $this->receipt->id]),
                'subsPlan' => $subsPlan,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->receiptPath)
            ->as($this->pdf)
            ->withMime('application/pdf'),
        ];
    }
}
