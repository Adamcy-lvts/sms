<?php

namespace App\Models;

use App\Models\User;
use App\Models\Agent;
use App\Models\Payment;
use App\Models\SubsPayment;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Status extends Model
{
    use HasFactory;

    protected $fillable = ['name','type'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function agents()
    {
        return $this->hasMany(Agent::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }


    public function subscriptionsPayments()
    {
        return $this->hasMany(SubsPayment::class);
    }



}
