<?php

namespace App\Models;

use App\Models\User;
use App\Models\School;
use App\Models\Feedback;
use Illuminate\Database\Eloquent\Model;

class FeedbackResponse extends Model
{
    protected $fillable = [
        'feedback_id',    // Links to main Feedback campaign
        'school_id',      // School that provided response
        'user_id',        // User who responded
        'responses',      // JSON field storing question responses
        'comments',       // Optional comments
        'rating'         // Overall rating
    ];

    protected $casts = [
        'responses' => 'array',  // Automatically cast JSON to array
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

    // Relationship to User who responded
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper scope to get responses for a specific school
    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }
}
