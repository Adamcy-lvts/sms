<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubscriptionReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'school_id',
        'payment_date',
        'receipt_for',
        'amount',
        'qr_code',
        'receipt_number',
        'remarks'
    ];

    public static function generateReceiptNumber($paymentDate)
    {
        // Convert paymentDate to Carbon instance
        $date = Carbon::parse($paymentDate);

        // Create the base receipt number format: dmy
        $baseNumber = $date->format('dmy');

        // Get the total receipts for this date and add 1
        $countForToday = SubscriptionReceipt::whereDate('created_at', $date)->count();
        $sequenceNumber = $countForToday + 1;

        // Get the current second
        // $currentSecond = Carbon::now()->second;

        // Combine to create the final receipt number
        return $baseNumber . str_pad($sequenceNumber, 3, '0', STR_PAD_LEFT);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
