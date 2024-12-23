<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssessmentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'max_score',
        'weight',
        'code',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_score' => 'integer',
        'weight' => 'integer'
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function assessments()
    {
        return $this->hasMany(SubjectAssessment::class);
    }
}
