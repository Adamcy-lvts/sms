<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReportAssessmentColumn extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_template_id',
        'name',
        'key',
        'max_score',
        'weight',
        'order',
        'is_visible'
    ];

    protected $casts = [
        'max_score' => 'integer',
        'weight' => 'integer',
        'is_visible' => 'boolean'
    ];

    public function template()
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }
}