<?php

namespace App\Models;

use App\Models\ClassRoom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttendanceSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'student_id',
        'class_room_id',
        'academic_session_id',
        'term_id',
        'total_days',
        'present_count',
        'absent_count',
        'late_count',
        'excused_count',
        'attendance_percentage'
    ];

    protected $casts = [
        'attendance_percentage' => 'decimal:2'
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

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

    public static function calculateForStudent(Student $student, int $academicSessionId, int $termId, int $totalSchoolDays = null): self {
        
        $records = AttendanceRecord::where([
            'student_id' => $student->id,
            'academic_session_id' => $academicSessionId,
            'term_id' => $termId,
        ])->get();

        $stats = [
            'total_days' => $totalSchoolDays ?? $records->count(), // Use provided school days or fallback
            'present_count' => $records->where('status', 'present')->count(),
            'absent_count' => $records->where('status', 'absent')->count(),
            'late_count' => $records->where('status', 'late')->count(),
            'excused_count' => $records->where('status', 'excused')->count(),
        ];

        $stats['attendance_percentage'] = $stats['total_days'] > 0
            ? (($stats['present_count'] + $stats['late_count']) / $stats['total_days']) * 100
            : 0;

        return static::updateOrCreate(
            [
                'student_id' => $student->id,
                'class_room_id' => $student->class_room_id,
                'school_id' => $student->school_id,
                'academic_session_id' => $academicSessionId,
                'term_id' => $termId,
            ],
            $stats
        );
    }
}
