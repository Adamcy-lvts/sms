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

    const LEVELS = [
        'NURSERY' => 'Nursery',
        'PRIMARY' => 'Primary',
        'SECONDARY' => 'Secondary'
    ];

    protected $fillable = [
        'school_id',
        'name',
        'category',
        'amount',
        'active',
        'has_due_date',
        'description',

    ];

    public static function getTuitionFeeForLevel(string $level)
    {
        return static::where('school_id', Filament::getTenant()->id)
            ->where('name', 'LIKE', "Tuition Fee - {$level}%")
            ->where('active', true)
            ->first();
    }

    public function isTuitionFee(): bool
    {
        return str_starts_with($this->name, 'Tuition Fee -');
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
}
