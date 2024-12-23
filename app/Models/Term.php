<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\School;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Term extends Model
{
    use HasFactory;

    protected $fillable = ['school_id', 'academic_session_id', 'name', 'start_date', 'end_date', 'is_current'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function ($term) {
            // Step 1: Check if this term is being set as current
            if ($term->is_current) {
                // Step 2: Find all other terms in the same academic session
                // that are currently set as current (excluding this one)
                $otherCurrentTerms = static::where('academic_session_id', $term->academic_session_id)
                    ->where('school_id', $term->school_id)
                    ->where('id', '!=', $term->id)
                    ->where('is_current', true);

                // Step 3: If any found, update them to not be current
                if ($otherCurrentTerms->exists()) {
                    // Step 4: Perform the update
                    $otherCurrentTerms->update(['is_current' => false]);

                    // Step 5: Optional - Log this change
                    // \Log::info("Unset previous current term(s) for academic session: {$term->academic_session_id}");
                }
            }
        });
    }

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($term) {
            if ($term->is_current) {
                Cache::tags("school:{$term->school->slug}")
                    ->forget("academic_period:{$term->school_id}");
            }
        });
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public static function getCurrentTerm()
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
