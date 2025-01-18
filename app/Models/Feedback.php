<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $fillable = [
        'title',
        'description',
        'target_schools', // JSON field for targeting specific schools
        'frequency_days',
        'start_date',
        'end_date',
        'is_active',
        'questions',     // JSON field to store feedback questions
    ];

    protected $casts = [
        'target_schools' => 'array',
        'questions' => 'array',
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime'
    ];

    // Relationships
    public function responses()
    {
        return $this->hasMany(FeedbackResponse::class);
    }

    public function tracking()
    {
        return $this->hasMany(FeedbackTracking::class);
    }

    public function isActive(): bool
    {
        return $this->is_active
            && $this->start_date->isPast()
            && ($this->end_date === null || $this->end_date->isFuture());
    }

    public function canShowToSchool(School $school): bool
    {
        // Check if feedback targets this school
        if (!empty($this->target_schools) && !in_array($school->id, $this->target_schools)) {
            return false;
        }

        // Check tracking
        $tracking = $this->tracking()
            ->where('school_id', $school->id)
            ->latest('last_shown_at')
            ->first();

        if (!$tracking) {
            return true;
        }

        return $tracking->canShowFeedback();
    }

    // Scope to get active feedback campaigns
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    // Scope to get feedback for specific school
    public function scopeForSchool($query, $schoolId)
    {
        return $query->where(function ($query) use ($schoolId) {
            $query->whereJsonContains('target_schools', $schoolId)
                ->orWhereNull('target_schools');
        });
    }

    // Get analytics for this feedback campaign
    // public function getAnalytics(): array
    // {
    //     return [
    //         'total_responses' => $this->responses()->count(),
    //         'average_rating' => $this->responses()->avg('rating'),
    //         'response_by_school' => $this->responses()
    //             ->select('school_id', DB::raw('count(*) as count'))
    //             ->groupBy('school_id')
    //             ->with('school:id,name')
    //             ->get()
    //             ->pluck('count', 'school.name'),
    //         'rating_distribution' => $this->responses()
    //             ->select('rating', DB::raw('count(*) as count'))
    //             ->groupBy('rating')
    //             ->pluck('count', 'rating'),
    //     ];
    // }
}
