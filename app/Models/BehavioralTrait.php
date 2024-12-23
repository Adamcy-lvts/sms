<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BehavioralTrait extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'category',
        'description',
        'display_order',
        'weight',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'weight' => 'float'
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function studentTraits()
    {
        return $this->hasMany(StudentTermTrait::class);
    }
}
