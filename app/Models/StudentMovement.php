<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentMovement extends Model
{
    protected $fillable = [
        'school_id',
        'student_id',
        'from_class_id',
        'to_class_id',
        'from_session_id',
        'to_session_id',
        'movement_type', // promotion, transfer, withdrawal, graduation, etc.
        'movement_date',
        'reason',
        'academic_performance',
        'requires_new_admission',
        'status',
        'processed_by',
        'destination_school', // For transfers
        'withdrawal_reason', // For withdrawals
        'completion_certificate', // For graduations
    ];

    protected $casts = [
        'movement_date' => 'date',
        'academic_performance' => 'array',
        'requires_new_admission' => 'boolean',
    ];

    // Define valid movement types
    public const MOVEMENT_TYPES = [
        'promotion' => 'Promotion to next class',
        'repeat' => 'Repeat current class',
        'transfer' => 'Transfer to another school',
        'withdrawal' => 'Withdrawal from school',
        'graduation' => 'Graduation',
        'demotion' => 'Demotion to lower class',
    ];


    // Movement requires destination school
    public const REQUIRES_DESTINATION = ['transfer'];
    
    // Movement terminates enrollment
    public const TERMINATES_ENROLLMENT = ['transfer', 'withdrawal', 'graduation'];

    // school relationship
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fromClass(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'from_class_id');
    }

    public function toClass(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'to_class_id');
    }

    public function fromSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'from_session_id');
    }

    public function toSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'to_session_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Helper methods
    public function requiresDestinationSchool(): bool
    {
        return in_array($this->movement_type, self::REQUIRES_DESTINATION);
    }

    public function terminatesEnrollment(): bool
    {
        return in_array($this->movement_type, self::TERMINATES_ENROLLMENT);
    }

    public function getMovementDescriptionAttribute(): string
    {
        return match ($this->movement_type) {
            'transfer' => "Transferred to {$this->destination_school}",
            'withdrawal' => "Withdrawn - {$this->withdrawal_reason}",
            'graduation' => "Graduated from {$this->fromClass->name}",
            'promotion' => "Promoted from {$this->fromClass->name} to {$this->toClass->name}",
            'demotion' => "Demoted from {$this->fromClass->name} to {$this->toClass->name}",
            default => $this->movement_type,
        };
    }
}
