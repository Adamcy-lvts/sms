<?php

// app/Models/ActivityType.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActivityType extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'category',
        'description',
        'is_default',
        'display_order',
        'icon',
        'color'
    ];

    protected $casts = [
        'is_default' => 'boolean'
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function studentActivities()
    {
        return $this->hasMany(StudentTermActivity::class);
    }
}
