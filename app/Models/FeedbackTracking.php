<?php

namespace App\Models;

use App\Models\School;
use App\Models\Feedback;
use Illuminate\Database\Eloquent\Model;

class FeedbackTracking extends Model
{
    protected $fillable = [
        'feedback_id',    // Links to main Feedback campaign
        'school_id',      // School being tracked
        'last_shown_at'   // When feedback was last shown
    ];

    protected $casts = [
        'last_shown_at' => 'datetime',  // Cast to Carbon instance
    ];

    // Relationship to main Feedback campaign
    public function feedback()
    {
        return $this->belongsTo(Feedback::class);
    }

    // Relationship to School
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    // Helper method to check if feedback can be shown again
    public function canShowFeedback()
    {
        // If no last_shown_at, feedback hasn't been shown yet
        if (!$this->last_shown_at) {
            return true;
        }

        // Check if enough days have passed based on feedback frequency
        return $this->last_shown_at->addDays($this->feedback->frequency_days)->isPast();
    }

    // Helper scope to find tracking record for a school
    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    // Helper scope to get active tracking records
    public function scopeActive($query)
    {
        return $query->whereHas('feedback', function ($q) {
            $q->where('is_active', true)
                ->where('start_date', '<=', now())
                ->where(function ($q) {
                    $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', now());
                });
        });
    }
}
