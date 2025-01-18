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

    protected $fillable = ['school_id', 'name', 'start_date', 'end_date', 'is_current'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function ($session) {
            // Only handle other sessions if this session is being set as current
            if ($session->isDirty('is_current') && $session->is_current) {
                // Reset other current sessions for the same school
                static::query()
                    ->where('school_id', $session->school_id)
                    ->where('id', '!=', $session->id)
                    ->update(['is_current' => false]);
            }
        });

        static::saved(function ($session) {
            if ($session->wasChanged('is_current')) {
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
