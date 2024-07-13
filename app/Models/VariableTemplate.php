<?php

namespace App\Models;

use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VariableTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'variable_name', 'mapping', 'school_id'
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }
}
