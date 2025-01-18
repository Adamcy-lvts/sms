<?php

namespace App\Models;

use App\Models\Subscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plan extends Model
{
    use HasFactory;

    /**
     * Billing intervals available (aligning with Paystack requirements)
     */
    const INTERVAL_MONTHLY = 'monthly';
    const INTERVAL_ANNUALLY = 'annually';

    /**
     * Plan status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_ARCHIVED = 'archived';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',             // Plan name (e.g., Basic Monthly, Basic Annual)
        'description',      // Detailed plan description
        'price',           // Original price in currency units
        'discounted_price', // Discounted price for annual plans
        'interval',        // Billing interval (monthly/annually)
        'duration',        // Plan duration in days (30 for monthly, 365 for annually)
        'features',        // Array of features included in the plan
        'plan_code',       // Paystack plan code
        'yearly_discount', // Percentage discount for annual plans
        'trial_period',    // Trial period in days
        'has_trial',      // Whether trial is enabled
        'status',         // Plan status (active/inactive/archived)
        'badge_color',    // Color for UI display
        'cto',            // Call to action text
        'max_students',   // Maximum students allowed
        'max_staff',      // Maximum staff allowed
        'max_classes'     // Maximum classes allowed
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'yearly_discount' => 'integer',
        'has_trial' => 'boolean',
        'max_students' => 'integer',
        'max_staff' => 'integer',
        'max_classes' => 'integer',
        'trial_period' => 'integer'
    ];

    /**
     * Default attribute values
     */
    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
        'has_trial' => false,
        'trial_period' => 0,
        'yearly_discount' => 0
    ];

      // Helper method to check if plan offers trial
      public function offersTrial(): bool 
      {
          return $this->has_trial && $this->trial_period > 0;
      }

    /**
     * Get the display price (discounted if available)
     */
    public function getDisplayPrice(): float
    {
        return $this->discounted_price ?? $this->price;
    }

    /**
     * Get base monthly price
     */
    public function getBaseMonthlyPrice(): float
    {
        return $this->interval === self::INTERVAL_MONTHLY ?
            $this->price :
            $this->price / 12;
    }

    /**
     * Get actual monthly price (including discount if applicable)
     */
    public function getActualMonthlyPrice(): float
    {
        $displayPrice = $this->getDisplayPrice();
        return $this->interval === self::INTERVAL_MONTHLY ?
            $displayPrice :
            $displayPrice / 12;
    }

    /**
     * Get yearly savings amount
     */
    public function getYearlySavingsAttribute(): float
    {
        if ($this->interval !== self::INTERVAL_ANNUALLY || !$this->yearly_discount) {
            return 0;
        }

        return $this->price - ($this->discounted_price ?? $this->price);
    }

    /**
     * Get yearly savings percentage
     */
    public function getYearlySavingsPercentageAttribute(): float
    {
        if ($this->interval !== self::INTERVAL_ANNUALLY || !$this->discounted_price) {
            return 0;
        }

        return $this->yearly_discount ?? 0;
    }

    /**
     * Get formatted savings text
     */
    public function getFormattedSavingsAttribute(): string
    {
        if (!$this->hasDiscount()) {
            return '';
        }

        return "Save {$this->yearly_discount}% with annual billing";
    }

    /**
     * Get formatted price with interval
     */
    public function getFormattedPriceAttribute(): string
    {
        $price = $this->getDisplayPrice();
        return formatNaira($price) . '/' .
            ($this->interval === self::INTERVAL_ANNUALLY ? 'year' : 'month');
    }

    /**
     * Check if plan is on trial
     */
    public function isOnTrial(): bool
    {
        return $this->has_trial && $this->trial_period > 0;
    }

    /**
     * Check if plan has a discount
     */
    public function hasDiscount(): bool
    {
        return $this->interval === self::INTERVAL_ANNUALLY &&
            $this->yearly_discount > 0 &&
            $this->discounted_price !== null;
    }

    /**
     * Check if plan has usage limits
     */
    public function hasLimits(): bool
    {
        return $this->max_students !== null ||
            $this->max_staff !== null ||
            $this->max_classes !== null;
    }

    /**
     * Check if plan includes a specific feature
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features) ||
            in_array('All Basic Features', $this->features) ||
            in_array('All Standard Features', $this->features);
    }

    /**
     * Get all subscriptions for this plan
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    /**
     * Get only active subscriptions
     */
    public function activeSubscriptions()
    {
        return $this->subscriptions()->where('status', 'active');
    }

    /**
     * Query Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeMonthly($query)
    {
        return $query->where('interval', self::INTERVAL_MONTHLY);
    }

    public function scopeAnnually($query)
    {
        return $query->where('interval', self::INTERVAL_ANNUALLY);
    }

    /**
     * Get all unique features across all plans
     */
    public static function allFeatures(): array
    {
        return self::pluck('features')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();
    }
}
