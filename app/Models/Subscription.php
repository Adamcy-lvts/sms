<?php

namespace App\Models;

use App\Models\Plan;
use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'features' // assuming you want to be able to set features directly
    ];

    // Relationship with Plan
    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
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

    public function updateSubscription($data)
    {
        // Update subscription details
        $this->update($data);
    }

    public function cancelSubscription()
    {
        // Cancel the subscription
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function isActive()
    {
        // Check if the subscription is active
        return $this->status === 'active' && (!$this->ends_at || $this->ends_at > now());
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
}
