<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReportCommentSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_template_id',
        'name',
        'type',
        'options',
        'order',
        'is_required',
        'is_visible'
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'is_visible' => 'boolean'
    ];

    public function template()
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }
}