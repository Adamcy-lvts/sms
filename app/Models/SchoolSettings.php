<?php

namespace App\Models;

use App\Models\School;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolSettings extends Model
{
    /** Cache settings */
    protected $cacheKey = 'school_settings';
    protected $cacheTTL = 86400; // 24 hour cache duration

    protected $fillable = [
        'school_id',
        'employee_id_settings',
        'academic_settings',
        'admission_settings'
    ];

    protected $attributes = [
        'admission_number_format_type' => 'with_year',
        'admission_number_prefix' => 'ADM',
        'admission_number_length' => 4,
        'admission_number_separator' => '/',
        'school_initials_method' => 'first_letters',
        'session_format' => 'short',
        'admission_number_start' => 1,
        'reset_sequence_yearly' => false,
        'reset_sequence_by_session' => false,
    ];

    protected $casts = [
        'employee_id_settings' => 'array',
        'academic_settings' => 'array',
        'admission_settings' => 'array'
    ];



    /** Clear cache when settings are saved */
    protected static function booted()
    {
        static::saved(function ($settings) {
            Cache::tags(["school:{$settings->school->slug}"])
                ->forget($settings->getCacheKey());
        });
    }

    /** Generate unique cache key per school */
    public function getCacheKey(): string
    {
        return "{$this->cacheKey}:{$this->school_id}";
    }

    /**
     * Get cached settings for a school
     * Creates cache if it doesn't exist
     * TTL: 24 hours
     */
    public static function getSettings($schoolId)
    {
        $school = School::find($schoolId);
        return Cache::tags(["school:{$school->slug}"])->remember(
            "school_settings:{$schoolId}",
            86400,
            fn() => self::where('school_id', $schoolId)->first()
        );
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
