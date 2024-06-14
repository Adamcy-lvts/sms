<?php

namespace App\Models;

use App\Models\Agent;
use App\Models\School;
use App\Models\Subscription;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
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
        });
    }
    
    public function paymentMethod()
    {

        return $this->belongsTo(PaymentMethod::class);

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
