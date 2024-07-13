<?php

namespace App\Models;

use App\Models\School;
use App\Models\AcademicSession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Term extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'academic_session_id', 'name', 'start_date', 'end_date'
    ];

    public function session()
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
