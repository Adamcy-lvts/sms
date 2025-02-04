<?php

namespace App\Models;

use App\Models\Payment;
use App\Models\PaymentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'payment_type_id',
        'amount',
        'deposit',
        'balance',
        'quantity',
        'unit_price',
        'is_tuition',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'deposit' => 'decimal:2',
        'balance' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'is_tuition' => 'boolean',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class);
    }
}
