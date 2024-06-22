<x-mail::message>
# Payment Receipt

Thank you for your recent payment to {{ config('app.name') }}. Below are the details of your transaction:

@component('mail::panel')
**Plan:** {{ $subsPlan }}  

@if(!$subscription->is_on_trial)
**Amount Paid:** ${{ formatNaira($subsPayment->amount) }}  
**Date:** {{ formatDate($receipt->payment_date) }}  
**Payment Method:** {{ ucfirst($subsPayment->method) ?? null }}
@endif

@if($subscription->is_on_trial)
You have started a {{$subscription->plan->duration}} days trial period for this subscription.

**Trial Period Ends:** {{ $trialEndsAt->diffForHumans() }}
@endif


A PDF copy of your receipt has been attached to this email for your records.

@if(isset($urlToReceipt))
    <x-mail::button :url="$urlToReceipt">
        View Receipt
    </x-mail::button>
@endif

If you have any questions or concerns regarding your payment, please contact our support team.

Thanks,<br>

@endcomponent
{{ config('app.name') }}
</x-mail::message>
