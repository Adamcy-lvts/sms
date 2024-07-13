<?php

namespace App\Models;

use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentType extends Model
{
    use HasFactory;
   
    protected $fillable = [
        'school_id',
        'name',
        'amount',
        'active',
        'description'
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
