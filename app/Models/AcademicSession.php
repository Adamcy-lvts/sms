<?php

namespace App\Models;

use App\Models\Term;
use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AcademicSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'name', 'start_date', 'end_date'
    ];

    public function terms()
    {
        return $this->hasMany(Term::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
