<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'school_id',
        'payment_type_id',
        'amount',
        'status',
        'payment_method_id',
        'reference',
        'description',
        'created_by',
        'updated_by'
    ];

    
}
