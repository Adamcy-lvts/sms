<?php

namespace App\Models;

use App\Models\Staff;
use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalaryPayment extends Model
{
    use HasFactory;


    protected $fillable = [
        'school_id',
        'staff_id',
        'amount',
        'status_id',
        'payment_date',
        'payment_method',
        'period_start',
        'period_end',
        'academic_year',
        'details',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'details' => 'array',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
