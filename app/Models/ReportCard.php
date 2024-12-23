<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportCard extends Model
{
    protected $fillable = [
        'school_id',
        'student_id',
        'class_room_id',
        'academic_session_id',
        'term_id',
        'template_id',
        'class_size',
        'position',
        'average_score',
        'total_subjects',
        'total_score',
        'subject_scores',
        'attendance_percentage',
        'monthly_attendance',
        'status',
        'created_by',
        'published_by',
        'published_at'
    ];

    protected $casts = [
        'subject_scores' => 'array',
        'monthly_attendance' => 'array',
        'published_at' => 'datetime'
    ];

    // Core relationships
    public function student()
    {
        return $this->belongsTo(Student::class);
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

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    // Helper scopes
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    // Analysis methods
    public function getGradeDistributionAttribute()
    {
        return StudentGrade::where([
            'student_id' => $this->student_id,
            'class_room_id' => $this->class_room_id,
            'academic_session_id' => $this->academic_session_id,
            'term_id' => $this->term_id,
        ])->with('assessment')->get()->groupBy('grade');
    }

    public function getSubjectPerformanceAttribute()
    {
        return StudentGrade::where([
            'student_id' => $this->student_id,
            'class_room_id' => $this->class_room_id,
            'academic_session_id' => $this->academic_session_id,
            'term_id' => $this->term_id,
        ])->with(['assessment.subject'])->get()
            ->groupBy('assessment.subject.name');
    }

    // Get student's best performing subjects
    public function getTopSubjects($limit = 3)
    {
        return collect($this->subject_scores)
            ->sortByDesc('total')
            ->take($limit)
            ->values();
    }

    // Get subject performance trend
    public static function getSubjectTrend(Student $student, $subjectId)
    {
        return self::where('student_id', $student->id)
            ->orderBy('academic_session_id')
            ->orderBy('term_id')
            ->get()
            ->map(function ($report) use ($subjectId) {
                $subjectScore = collect($report->subject_scores)
                    ->firstWhere('subject_id', $subjectId);

                return [
                    'term' => $report->term->name,
                    'session' => $report->academicSession->name,
                    'score' => $subjectScore['total'] ?? null
                ];
            });
    }
}
