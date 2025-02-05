<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\School;
use App\Models\Inventory;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentType extends Model
{
    use HasFactory;

    const CATEGORY_SERVICE = 'service_fee';
    const CATEGORY_PHYSICAL = 'physical_item';
    const TUITION_PREFIX = 'Tuition/School Fees';


    protected $fillable = [
        'school_id',
        'name',
        'category',
        'amount',
        'active',
        'is_tuition',
        'class_level',             // For tracking education level
        'installment_allowed',     // Allow partial payments
        'min_installment_amount',  // Minimum installment amount
        'has_due_date',
        'description',

    ];

    protected $casts = [
        'is_tuition' => 'boolean',
        'active' => 'boolean',
        'installment_allowed' => 'boolean',
        'has_due_date' => 'boolean'
    ];

    public function getMinPaymentAmount(): float
    {
        return $this->installment_allowed ? $this->min_installment_amount : $this->amount;
    }

    // Add helper method
    public function hasDueDate(): bool
    {
        return $this->has_due_date;
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    // Helper methods
    public function requiresInventory(): bool
    {
        return $this->category === self::CATEGORY_PHYSICAL;
    }

    public function isServiceFee(): bool
    {
        return $this->category === self::CATEGORY_SERVICE;
    }

    // Relationships
    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }

    public function getDueDate(Term $term): ?Carbon
    {
        try {
            $settings = School::find($this->school_id)->settings;
            $paymentSettings = $settings->payment_settings ?? [];

            // First try term_payment_types
            $termPaymentTypes = $paymentSettings['due_dates']['term_payment_types'] ?? [];

            // If term_payment_types is empty or doesn't have this payment type,
            // fall back to default_days
            $daysAfterStart = !empty($termPaymentTypes) && isset($termPaymentTypes[$this->id])
                ? (int) $termPaymentTypes[$this->id]
                : (int) ($paymentSettings['due_dates']['default_days'] ?? 7);

            return $term->start_date->addDays($daysAfterStart);
        } catch (\Exception $e) {
            // If anything goes wrong, return a default due date
            return $term->start_date->addDays(7);
        }
    }

    // Add relationship to payment plans
    public function paymentPlans()
    {
        return $this->hasMany(PaymentPlan::class);
    }

    // Helper to get amount for specific class level and period
    public function getAmountForClass(string $classLevel, string $period = 'session'): ?float
    {
        $plan = $this->paymentPlans()
            ->where('class_level', $classLevel)
            ->first();

        return $plan ? $plan->getAmount($period) : null;
    }
}
