<?php

namespace App\Models;

use App\Models\User;
use App\Models\Agent;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'slug', 'address','agent_id', 'phone', 'logo', 'settings'
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'school_user');
    }

    public function hasActiveSubscription($planId)
    {
        return $this->subscriptions()->active()->where('plan_id', $planId)->exists();
    }

    public function currentSubscription()
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->latest('starts_at')
            ->first();
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'school_id');
    }

    public function canRenewSubscription()
    {
        $subscription = $this->subscriptions()->latest('created_at')->first();
        return $subscription && $subscription->status === 'non-renewing'; // Adjust condition based on your logic
    }


}
