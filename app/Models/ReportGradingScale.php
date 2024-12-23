<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReportGradingScale extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_template_id',
        'grade',
        'min_score',
        'max_score',
        'remark',
        'color_code'
    ];

    protected $casts = [
        'min_score' => 'integer',
        'max_score' => 'integer'
    ];

    public function template()
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }
}