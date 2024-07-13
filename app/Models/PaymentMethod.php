<?php

namespace App\Models;

use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'school_id',
        'slug',
        'description',
        'logo',
        'active'
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
