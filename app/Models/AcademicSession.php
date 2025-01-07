<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\Term;
use App\Models\School;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AcademicSession extends Model
{
    use HasFactory;

    protected $fillable = ['school_id','name', 'start_date', 'end_date', 'is_current'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function ($session) {
            // Step 1: Check if this session is being set as current
            if ($session->is_current) {
                // Step 2: Find all other sessions for the same school
                // that are currently set as current (excluding this one)
                $otherCurrentSessions = static::where('school_id', $session->school_id)
                    ->where('id', '!=', $session->id)
                    ->where('is_current', true);

                // Step 3: If any found, update them to not be current
                if ($otherCurrentSessions->exists()) {
                    // Step 4: Perform the update
                    $otherCurrentSessions->update(['is_current' => false]);

                    // Step 5: Optional - Log this change
                    // \Log::info("Unset previous current session(s) for school: {$session->school_id}");
                }
            }
        });
    }
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($session) {
            if ($session->is_current) {
                Cache::tags("school:{$session->school->slug}")
                    ->forget("academic_period:{$session->school_id}");
            }
        });
    }

    public function terms()
    {
        return $this->hasMany(Term::class);
    }

    public static function getCurrentSession()
    {
        $now = Carbon::now();
        return self::where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->first();
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
