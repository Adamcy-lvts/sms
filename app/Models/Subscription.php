<?php

namespace App\Models;

use App\Models\Plan;
use App\Models\School;
use App\Models\SubsPayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'plan_id',
        'status',
        'starts_at',
        'ends_at',
        'cancelled_at',
        'trial_ends_at',
        'is_recurring',
        'subscription_code',
        'next_payment_date',
        'is_on_trial',
        'token',
        'features' // assuming you want to be able to set features directly
    ];

    /**
     * Get the plan associated with the subscription.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    // Relationship with School
    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public static function createSubscription($schoolId, $planId, $startDate)
    {
        // Check for an existing active subscription
        $activeSubscription = self::where('school_id', $schoolId)
            ->where('status', 'active')
            ->first();

        if ($activeSubscription) {
            // Handle existing active subscription based on business rules
            $activeSubscription->cancelSubscription();
        }

        // Create new subscription
        return self::create([
            'school_id' => $schoolId,
            'plan_id' => $planId,
            'status' => 'active',
            'starts_at' => $startDate,
            'is_recurring' => true,
        ]);
    }


    public function cancelSubscription()
    {
        // Cancel the subscription
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->ends_at > now();
    }

    public function hasFeature(string $feature): bool
    {
        return $this->isActive() && $this->plan->hasFeature($feature);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active'); // Modify this condition based on how you determine active subscriptions
    }

    public function renewSubscription()
    {
        // Renew the subscription if it's recurring
        if ($this->is_recurring && $this->isActive()) {
            $this->update([
                'ends_at' => $this->ends_at ? $this->ends_at->addDays($this->plan->duration) : now()->addDays($this->plan->duration),
            ]);
        }
    }

    public function payments()
    {
        return $this->hasMany(SubsPayment::class, 'subscription_id');
    }

    public function getPaymentStatus(): string
    {
        return $this->payments()->latest('created_at')->first()->status;
    }

    // Check if subscription is in trial period
    public function isOnTrial(): bool
    {
        return $this->is_on_trial &&
            $this->trial_ends_at &&
            $this->trial_ends_at->isFuture();
    }

    // Check if trial has expired
    public function trialHasExpired(): bool
    {
        return $this->is_on_trial &&
            $this->trial_ends_at &&
            $this->trial_ends_at->isPast();
    }

    // Start trial period
    public function startTrial(int $days = null): self
    {
        $this->is_on_trial = true;
        $this->trial_ends_at = now()->addDays($days ?? $this->plan->trial_period);
        $this->save();

        return $this;
    }

    public function getSuperAdmin()
    {
        return $this->school->members()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'super_admin')
                    ->where('team_id', $this->school_id);
            })
            ->first();
    }

    // Scope for trial subscriptions
    public function scopeOnTrial($query)
    {
        return $query->where('is_on_trial', true)
            ->where('status', 'active');
    }

    // Check if trial is ending soon
    public function isTrialEndingSoon(int $days = 3): bool
    {
        return $this->is_on_trial
            && $this->status === 'active'
            && $this->trial_ends_at->isNextDays($days);
    }

    public function checkLimit(string $limitField, int $currentCount): bool
    {
        if (!$this->plan->hasLimit($limitField)) {
            return true;
        }

        return $currentCount < $this->plan->$limitField;
    }

    public function getRemainingLimit(string $limitField, int $currentCount): int
    {
        return $this->plan->getRemainingLimit($limitField, $currentCount);
    }
}
