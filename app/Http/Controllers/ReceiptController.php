<?php

namespace App\Http\Controllers;

use App\Models\SubsPayment;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function show($payment, $receipt)
    {
        // dd( $receipt);
                $payment = SubsPayment::find($payment);
                $receipt = $payment->SubscriptionReceipt()->where('id', $receipt)->first();
        return view('pdfs.subscription_receipt_pdf', compact('payment', 'receipt'));
  
    }
}
