<?php

namespace App\Models;

use App\Models\Staff;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'school_id',
        'specialization',
        'teaching_experience',
    ];

    protected $casts = [
        'subject_ids' => 'array',
        'class_room_ids' => 'array',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class);
    }

    public function classRooms()
    {
        return $this->belongsToMany(ClassRoom::class);
    }

    public function hasClassRoom(ClassRoom $classRoom): bool
    {
        return $this->classRooms()->where('class_rooms.id', $classRoom->id)->exists();
    }
}
