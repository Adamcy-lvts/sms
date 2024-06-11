<?php

namespace App\Models;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubsPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'agent_id', 'amount', 'net_amount', 'split_amount_agent',
        'split_code', 'status', 'payment_method_id', 'reference', 'payment_date'
    ];


    public function paymentMethod() {

        return $this->belongsTo(PaymentMethod::class);
        
    }
}
