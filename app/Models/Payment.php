<?php

namespace App\Models;

use App\Models\Term;
use App\Models\User;
use App\Models\School;
use App\Models\Status;
use App\Models\Student;
use App\Models\ClassRoom;
use App\Models\PaymentType;
use App\Models\PaymentMethod;
use App\Models\PaymentHistory;
use App\Models\AcademicSession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;


    protected $fillable = [
        'school_id',
        'student_id',
        'class_room_id',
        'receiver_id',
        'payment_method_id',
        'status_id',
        'academic_session_id',
        'term_id',
        'original_payment_id',
        'is_balance_payment',
        'is_tuition',
        'payment_plan_type',
        'payment_category',
        'reference',
        'payer_name',
        'payer_phone_number',
        'amount',
        'deposit',
        'balance',
        'meta_data',
        'remark',
        'due_date',
        'paid_at',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_tuition' => 'boolean',
        'is_balance_payment' => 'boolean',
        'meta_data' => 'array',
        'due_date' => 'datetime',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'deposit' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function shouldShowTerms(): bool
    {
        return $this->meta_data['show_terms'] ?? true;
    }

    public function getTerms(): array
    {
        return $this->meta_data['terms'] ?? [
            '1' => 'Payment is non-refundable.',
            '2' => 'Please keep this receipt for your records.'
        ];
    }

    public function getPaymentTermsAttribute(): array
    {
        return $this->meta_data['terms'] ?? [
            'Payment is non-refundable.',
            'Please keep this receipt for your records.'
        ];
    }

    public function setPaymentTermsAttribute(array $terms)
    {
        $metaData = $this->meta_data ?? [];
        $metaData['terms'] = $terms;
        $this->meta_data = $metaData;
    }

    /**
     * Get the user who created the payment.
     */
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Get the user who updated the payment.
     */
    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function originalPayment()
    {
        return $this->belongsTo(Payment::class, 'original_payment_id');
    }

    public function balancePayments()
    {
        return $this->hasMany(Payment::class, 'original_payment_id');
    }

    public function paymentItems()
    {
        return $this->hasMany(PaymentItem::class);
    }

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

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_room_id');
    }

    public function scopeForSession($query, $sessionId)
    {
        return $query->where('academic_session_id', $sessionId);
    }

    public function scopeForTerm($query, $termId)
    {
        return $query->where('term_id', $termId);
    }

    public function scopeTuitionPayments($query)
    {
        return $query->where('is_tuition', true);
    }
}
