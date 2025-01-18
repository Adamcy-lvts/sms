<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusChange extends Model
{
    protected $fillable = [
        'school_id',
        'student_id',
        'statusable_type',
        'statusable_id',
        'from_status_id',
        'to_status_id',
        'reason',
        'metadata',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'changed_at' => 'datetime',
    ];

    // Relationships
    public function statusable(): MorphTo
    {
        return $this->morphTo();
    }

    public function fromStatus(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'from_status_id');
    }

    public function toStatus(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'to_status_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Scope a query to only include student status changes.
     */
    public function scopeStudents(Builder $query): Builder
    {
        return $query->where('statusable_type', Student::class);
    }

    /**
     * Scope a query to only include status changes for a specific status.
     */
    public function scopeForStatus(Builder $query, string $statusName): Builder
    {
        return $query->whereHas('toStatus', fn($q) => $q->where('name', $statusName));
    }

    /**
     * Get the student if the status change belongs to a student.
     */
    public function student()
    {
        if ($this->statusable_type !== Student::class) {
            return null;
        }
        
        return $this->belongsTo(Student::class, 'statusable_id');
    }

    /**
     * Get a human-readable description of the status change.
     */
    public function getChangeDescriptionAttribute(): string
    {
        return sprintf(
            'Changed from %s to %s on %s',
            $this->fromStatus?->name ?? 'None',
            $this->toStatus->name,
            $this->changed_at->format('j M, Y H:i')
        );
    }
}
