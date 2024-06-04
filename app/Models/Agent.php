<?php

namespace App\Models;

use App\Models\Bank;
use App\Models\User;
use App\Models\School;
use Illuminate\Support\Str;
use App\Models\AgentPayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Agent extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'business_name', 'account_name', 'account_number', 'bank_id', 'percentage', 'fixed_rate', 'referral_code', 'subaccount_code'];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($agent) {
            $agent->referral_code = Str::random(10); // Generate a random string; consider a more structured approach if necessary
            $agent->save();
        });
    }

    public function schools()
    {
        return $this->hasMany(School::class, 'agent_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function agentPayments()
    {
        return $this->hasMany(AgentPayment::class);
    }


}
