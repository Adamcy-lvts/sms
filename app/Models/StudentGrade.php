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
        'subject_id',              // Add direct subject relation instead of via assessment
        'assessment_type_id',      // Direct link to assessment type
        'class_room_id',          // Add class room for easier querying
        'academic_session_id',    // Add academic session
        'term_id',               // Add term
        'score',
        'remarks',
        'recorded_by',
        'modified_by',
        'graded_at',
        'assessment_date',       // Optional: when the assessment was taken
        'is_published'          // For controlling visibility
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'graded_at' => 'datetime',
        'assessment_date' => 'datetime',
        'is_published' => 'boolean'
    ];

    // Core relationships
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function assessmentType()
    {
        return $this->belongsTo(AssessmentType::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function modifiedBy()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    // Helper methods
    public function getGrade()
    {
        return GradingScale::getGrade($this->score, $this->school_id);
    }

    // Scopes for easier querying
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeForTerm($query, $termId)
    {
        return $query->where('term_id', $termId);
    }

    public function scopeForSession($query, $sessionId)
    {
        return $query->where('academic_session_id', $sessionId);
    }

    public function scopeForAssessmentType($query, $typeId)
    {
        return $query->where('assessment_type_id', $typeId);
    }
}
