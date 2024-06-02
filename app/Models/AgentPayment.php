<?php

namespace App\Models;

use App\Models\Agent;
use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AgentPayment extends Model
{
    use HasFactory;

    protected $fillable = ['agent_id', 'school_id', 'amount', 'split_code', 'status', 'payment_date'];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
