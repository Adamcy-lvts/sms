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
            // Only handle other terms if this term is being set as current
            if ($term->isDirty('is_current') && $term->is_current) {
                // Reset other current terms for the same school and academic session
                static::query()
                    ->where('school_id', $term->school_id)
                    ->where('academic_session_id', $term->academic_session_id)
                    ->where('id', '!=', $term->id)
                    ->update(['is_current' => false]);
            }
        });

        static::saved(function ($term) {
            if ($term->wasChanged('is_current')) {
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
