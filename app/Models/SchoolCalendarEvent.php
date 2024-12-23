<?php

namespace App\Models;

use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SchoolCalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'type', // holiday, event, break
        'is_recurring',
        'recurrence_pattern',
        'excludes_attendance',
        'color',
        'excluded_dates'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_recurring' => 'boolean',
        'excludes_attendance' => 'boolean',
        'excluded_dates' => 'array'
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
