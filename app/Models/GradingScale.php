<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GradingScale extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'grade',
        'min_score',
        'max_score',
        'remark',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_score' => 'integer',
        'max_score' => 'integer'
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public static function getGrade($score, $schoolId)
    {
        return static::where('school_id', $schoolId)
            ->where('is_active', true)
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->first();
    }
}