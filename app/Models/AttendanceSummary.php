<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\ClassRoom;
use App\Services\CalendarService;
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

    public static function calculateForStudent(Student $student, int $academicSessionId, int $termId, int $totalSchoolDays = null): self
    {

        // Get term dates
        $term = Term::find($termId);
        $termStart = Carbon::parse($term->start_date);
        $today = Carbon::today();

        // Get attendance records
        $records = AttendanceRecord::where([
            'student_id' => $student->id,
            'academic_session_id' => $academicSessionId,
            'term_id' => $termId,
        ])
            ->whereDate('date', '<=', $today)  // Only count up to current date
            ->get();

        // Calculate actual school days elapsed
        $calendarService = app(CalendarService::class);
        $schoolDays = $calendarService->getSchoolDays($student->school, $term);
        $elapsedSchoolDays = $schoolDays['elapsed_days'] ?? $totalSchoolDays;

        $stats = [
            'total_days' => $elapsedSchoolDays,
            'present_count' => $records->where('status', 'present')->count(),
            'absent_count' => $records->where('status', 'absent')->count(),
            'late_count' => $records->where('status', 'late')->count(),
            'excused_count' => $records->where('status', 'excused')->count(),
        ];

        $stats['attendance_percentage'] = $elapsedSchoolDays > 0
            ? (($stats['present_count'] + $stats['late_count']) / $elapsedSchoolDays) * 100
            : 0;

        return static::updateOrCreate([
            'student_id' => $student->id,
            'class_room_id' => $student->class_room_id,
            'school_id' => $student->school_id,
            'academic_session_id' => $academicSessionId,
            'term_id' => $termId,
        ], $stats);
    }
}
