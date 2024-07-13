<?php

namespace App\Models;

use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClassRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'school_id', 'capacity',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
