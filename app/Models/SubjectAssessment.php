<?php

namespace App\Models;

use App\Models\Term;
use App\Models\User;
use App\Models\School;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\ClassRoom;
use App\Models\StudentGrade;
use App\Models\AssessmentType;
use App\Models\AcademicSession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubjectAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'subject_id',
        'class_room_id',
        'teacher_id',
        'academic_session_id',
        'term_id',
        'assessment_type_id',
        'title',
        'assessment_date',
        'description',
        'is_published',
        'published_at',
        'created_by'
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'is_published' => 'boolean',
        'published_at' => 'datetime'
    ];

    // Relationships
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function assessmentType()
    {
        return $this->belongsTo(AssessmentType::class);
    }

    public function grades()
    {
        return $this->hasMany(StudentGrade::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
