<?php

namespace App\Models;

use App\Models\User;
use App\Models\School;
use App\Models\Student;
use App\Models\GradingScale;
use App\Models\SubjectAssessment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'student_id',
        'subject_assessment_id',
        'score',
        'remarks',
        'recorded_by',
        'modified_by',
        'graded_at'
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'graded_at' => 'datetime'
    ];

    // Relationships
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function assessment()
    {
        return $this->belongsTo(SubjectAssessment::class, 'subject_assessment_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function modifiedBy()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    // Get the letter grade based on the score
    public function getGrade()
    {
        return GradingScale::getGrade($this->score, $this->school_id);
    }
}
