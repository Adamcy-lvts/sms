<?php

namespace App\Models;

use App\Models\Term;
use App\Models\User;
use App\Models\School;
use App\Models\Status;
use App\Models\Student;
use App\Models\PaymentType;
use App\Models\PaymentMethod;
use App\Models\AcademicSession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id',
        'school_id',
        'academic_session_id',
        'term_id',
        'student_id',
        'payment_type_id',
        'payment_method_id',
        'status_id',
        'amount',
        'deposit',
        'balance',
        'due_date',
        'paid_at',
        'payer_name',
        'payer_phone_number',
        'reference',
        'description',
        'created_by',
        'updated_by'
    ];

    public function user()
    {
        return $this->belongsTo(User::class,);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    

    
    
}
