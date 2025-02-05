<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentPlan extends Model
{
    protected $fillable = [
        'school_id',
        'payment_type_id',
        'name',
        'class_level',
        'session_amount',
        'term_amount'
    ];

    // Get the appropriate amount based on payment period
    public function getAmount(string $period = 'term'): float
    {
        return $period === 'session' ? $this->session_amount : $this->term_amount;
    }

    // Relationships
    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
