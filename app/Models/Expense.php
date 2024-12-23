<?php

namespace App\Models;

use App\Models\User;
use App\Models\School;
use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'school_id',
        'expense_category_id',
        'academic_session_id',
        'term_id',
        'amount',
        'reference',
        'description',
        'expense_date',
        'payment_method',
        'recorded_by',
        'approved_by',
        'receipt_number',
        'expense_items',
        'status'
    ];

    protected $casts = [
        'expense_items' => 'array',
        'expense_date' => 'date',
        'amount' => 'decimal:2'
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
