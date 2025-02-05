<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentPaymentPlan extends Model
{
    protected $fillable = [
        'school_id',
        'student_id',
        'payment_plan_id',
        'academic_session_id',
        'created_by',
        'notes'
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function paymentPlan()
    {
        return $this->belongsTo(PaymentPlan::class);
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    // Helper methods
    public function getAmount(string $period = 'term'): float
    {
        return $this->paymentPlan->getAmount($period);
    }
}
