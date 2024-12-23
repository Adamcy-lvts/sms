<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReportSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_template_id',
        'name',
        'type',
        'order',
        'config',
        'is_visible'
    ];

    protected $casts = [
        'config' => 'array',
        'is_visible' => 'boolean'
    ];

    public function template()
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }
}