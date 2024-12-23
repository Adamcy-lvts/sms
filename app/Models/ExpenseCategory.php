<?php

namespace App\Models;

use App\Models\School;
use App\Models\Expense;
use App\Models\ExpenseItem;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'type',
        'code',
        'description'
    ];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function expenseItems()
    {
        return $this->hasMany(ExpenseItem::class);
    }
}
