<?php

namespace App\Models;

use App\Models\Staff;
use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Qualification extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'staff_id',
        'qualifications',
        // 'name',
        // 'institution',
        // 'year_obtained',
        // 'document_path',
    ];

    protected $casts = [
        'qualifications' => 'array',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
