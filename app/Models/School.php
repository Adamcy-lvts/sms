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

    // In the School model
    public function hasActiveSubscription()
    {
        return $this->subscriptions()->where('status', 'active')->first();
    }


    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'school_id');
    }


}
