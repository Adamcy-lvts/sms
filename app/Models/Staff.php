<?php

namespace App\Models;

use App\Models\Bank;
use App\Models\User;
use App\Models\School;
use App\Models\Status;
use App\Models\Teacher;
use App\Models\Designation;
use App\Models\Qualification;
use App\Models\SalaryPayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Staff extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'user_id',
        'designation_id',
        'employee_id',
        'status_id',
        'first_name',
        'last_name',
        'middle_name',
        'gender',
        'date_of_birth',
        'phone_number',
        'email',
        'address',
        'hire_date',
        'employment_status',
        'salary',
        'bank_id',
        'account_number',
        'account_name',
        'profile_picture',
        'emergency_contact',
    ];


    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name;
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function qualifications()
    {
        return $this->hasMany(Qualification::class);
    }

    public function salaryHistory()
    {
        return $this->hasMany(SalaryPayment::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
