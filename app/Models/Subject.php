<?php

namespace App\Models;

use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'school_id', 'position', 'color', 'icon', 'description', 'is_optional', 'is_active', 'is_archived'
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    
}
